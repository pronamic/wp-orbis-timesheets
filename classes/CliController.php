<?php
/**
 * CLI controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

use WP_CLI;

/**
 * CLI controller class
 */
class CliController {
	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'cli_init', $this->cli_init( ... ) );
	}

	/**
	 * CLI initialize.
	 *
	 * @return void
	 */
	public function cli_init() {
		$synopsis = [
			[
				'type'        => 'flag',
				'name'        => 'dry-run',
				'description' => 'Only report the number of timesheet entries that would be updated.',
				'optional'    => true,
			],
		];

		WP_CLI::add_command(
			'orbis-timesheets billability apply-excluded',
			fn( $args, $assoc_args ) => $this->apply_project_billability( Billability::Excluded, $assoc_args ),
			[
				'shortdesc' => 'Set the billability of timesheet entries without billability to excluded if their project is excluded.',
				'synopsis'  => $synopsis,
			]
		);

		WP_CLI::add_command(
			'orbis-timesheets billability apply-non-billable',
			fn( $args, $assoc_args ) => $this->apply_project_billability( Billability::NonBillable, $assoc_args ),
			[
				'shortdesc' => 'Set the billability of timesheet entries without billability to non-billable if their project is non-billable.',
				'synopsis'  => $synopsis,
			]
		);

		WP_CLI::add_command(
			'orbis-timesheets billability apply-billable',
			fn( $args, $assoc_args ) => $this->apply_billable_project_billability( $assoc_args ),
			[
				'shortdesc' => 'Set the billability of timesheet entries without billability on billable projects to billable or non-billable, based on the project budget.',
				'longdesc'  => 'Timesheet entries are billable as long as the time registered on the project before the entry date, plus the entry itself, does not exceed the project budget.',
				'synopsis'  => $synopsis,
			]
		);
	}

	/**
	 * Apply project billability to timesheet entries without billability.
	 *
	 * @param Billability $billability Project billability to apply.
	 * @param array       $assoc_args  Associative arguments.
	 * @return void
	 */
	private function apply_project_billability( Billability $billability, $assoc_args ) {
		global $wpdb;

		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( $dry_run ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT
						COUNT( timesheet.id )
					FROM
						$wpdb->orbis_timesheets AS timesheet
							INNER JOIN
						$wpdb->orbis_projects AS project
								ON timesheet.project_id = project.id
					WHERE
						timesheet.billability = ''
							AND
						project.billability = %s
					",
					$billability->value
				)
			);

			WP_CLI::success( \sprintf( '%d timesheet entries would be updated to %s.', $count, $billability->value ) );

			return;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"
				UPDATE
					$wpdb->orbis_timesheets AS timesheet
						INNER JOIN
					$wpdb->orbis_projects AS project
							ON timesheet.project_id = project.id
				SET
					timesheet.billability = project.billability
				WHERE
					timesheet.billability = ''
						AND
					project.billability = %s
				",
				$billability->value
			)
		);

		if ( false === $result ) {
			WP_CLI::error( \sprintf( 'Could not update timesheet entries: %s', $wpdb->last_error ) );
		}

		WP_CLI::success( \sprintf( '%d timesheet entries updated to %s.', $result, $billability->value ) );
	}

	/**
	 * Apply billable project billability to timesheet entries without billability.
	 *
	 * Uses window functions to calculate the time registered on the project before
	 * the entry date, instead of joining all previous timesheet entries per entry.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	private function apply_billable_project_billability( $assoc_args ) {
		global $wpdb;

		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		$summary = $wpdb->prepare(
			"
			SELECT
				timesheet.id,
				IF (
					SUM( timesheet.number_seconds ) OVER ( PARTITION BY timesheet.project_id ORDER BY timesheet.date )
						-
					SUM( timesheet.number_seconds ) OVER ( PARTITION BY timesheet.project_id, timesheet.date )
						+
					timesheet.number_seconds <= project.number_seconds,
					%s,
					%s
				) AS billability
			FROM
				$wpdb->orbis_timesheets AS timesheet
					INNER JOIN
				$wpdb->orbis_projects AS project
						ON timesheet.project_id = project.id
			WHERE
				project.billability = %s
			",
			Billability::Billable->value,
			Billability::NonBillable->value,
			Billability::Billable->value
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The summary query is prepared above.

		if ( $dry_run ) {
			$results = $wpdb->get_results(
				"
				SELECT
					summary.billability,
					COUNT( timesheet.id ) AS count
				FROM
					$wpdb->orbis_timesheets AS timesheet
						INNER JOIN
					( $summary ) AS summary
							ON summary.id = timesheet.id
				WHERE
					timesheet.billability = ''
				GROUP BY
					summary.billability
				"
			);

			if ( '' !== $wpdb->last_error ) {
				WP_CLI::error( \sprintf( 'Could not query timesheet entries: %s', $wpdb->last_error ) );
			}

			$counts = \array_column( $results, 'count', 'billability' );

			WP_CLI::success(
				\sprintf(
					'%d timesheet entries would be updated to %s, %d to %s.',
					$counts[ Billability::Billable->value ] ?? 0,
					Billability::Billable->value,
					$counts[ Billability::NonBillable->value ] ?? 0,
					Billability::NonBillable->value
				)
			);

			return;
		}

		$result = $wpdb->query(
			"
			UPDATE
				$wpdb->orbis_timesheets AS timesheet
					INNER JOIN
				( $summary ) AS summary
						ON summary.id = timesheet.id
			SET
				timesheet.billability = summary.billability
			WHERE
				timesheet.billability = ''
			"
		);

		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $result ) {
			WP_CLI::error( \sprintf( 'Could not update timesheet entries: %s', $wpdb->last_error ) );
		}

		WP_CLI::success( \sprintf( '%d timesheet entries updated to %s or %s.', $result, Billability::Billable->value, Billability::NonBillable->value ) );
	}
}
