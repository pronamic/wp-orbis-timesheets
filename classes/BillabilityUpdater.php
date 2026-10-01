<?php
/**
 * Billability updater
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

/**
 * Billability updater class
 *
 * Calculates the billability of timesheet entries based on their project:
 *
 * - Entries on excluded or non-billable projects get the project billability.
 * - Entries on billable projects are billable as long as the time registered on
 *   the project before the entry date, plus the entry itself, does not exceed the
 *   project budget, otherwise they are non-billable.
 * - Entries without a project are left untouched.
 */
class BillabilityUpdater {
	/**
	 * Update the billability of a timesheet entry.
	 *
	 * @param int $entry_id Timesheet entry ID.
	 * @return int|false Number of updated rows, or false on error.
	 */
	public function update_entry( $entry_id ) {
		global $wpdb;

		$project_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT project_id FROM $wpdb->orbis_timesheets WHERE id = %d;",
				$entry_id
			)
		);

		if ( null === $project_id ) {
			return 0;
		}

		return $this->update( (int) $project_id, $entry_id );
	}

	/**
	 * Update the billability of all timesheet entries of a project.
	 *
	 * @param int $project_id Orbis project ID.
	 * @return int|false Number of updated rows, or false on error.
	 */
	public function update_project( $project_id ) {
		return $this->update( $project_id );
	}

	/**
	 * Update the billability of timesheet entries of a project.
	 *
	 * @param int      $project_id Orbis project ID.
	 * @param int|null $entry_id   Optional timesheet entry ID to limit the update to.
	 * @return int|false Number of updated rows, or false on error.
	 */
	private function update( $project_id, $entry_id = null ) {
		global $wpdb;

		$summary = $wpdb->prepare(
			"
			SELECT
				timesheet.id,
				IF (
					project.billability = %s,
					IF (
						SUM( timesheet.number_seconds ) OVER ( ORDER BY timesheet.date )
							-
						SUM( timesheet.number_seconds ) OVER ( PARTITION BY timesheet.date )
							+
						timesheet.number_seconds <= project.number_seconds,
						%s,
						%s
					),
					COALESCE( project.billability, '' )
				) AS billability
			FROM
				$wpdb->orbis_timesheets AS timesheet
					INNER JOIN
				$wpdb->orbis_projects AS project
						ON timesheet.project_id = project.id
			WHERE
				timesheet.project_id = %d
			",
			Billability::Billable->value,
			Billability::Billable->value,
			Billability::NonBillable->value,
			$project_id
		);

		$where = '';

		if ( null !== $entry_id ) {
			$where = $wpdb->prepare( 'WHERE timesheet.id = %d', $entry_id );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The summary query and where clause are prepared above.

		return $wpdb->query(
			"
			UPDATE
				$wpdb->orbis_timesheets AS timesheet
					INNER JOIN
				( $summary ) AS summary
						ON summary.id = timesheet.id
			SET
				timesheet.billability = summary.billability
			$where
			"
		);

		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
