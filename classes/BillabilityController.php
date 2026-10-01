<?php
/**
 * Billability controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

use Exception;

/**
 * Billability controller class
 *
 * Schedules asynchronous billability updates through Action Scheduler when a
 * timesheet entry or project is saved.
 *
 * @link https://actionscheduler.org/
 */
class BillabilityController {
	/**
	 * Construct billability controller.
	 *
	 * @param BillabilityUpdater $updater Billability updater.
	 */
	public function __construct(
		private BillabilityUpdater $updater = new BillabilityUpdater()
	) {
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		\add_action( 'orbis_timesheets_entry_saved', $this->schedule_entry_update( ... ) );

		// Projects are synced to the `orbis_projects` table at priority 500.
		\add_action( 'save_post_orbis_project', $this->schedule_project_update( ... ), 1000 );

		\add_action( 'orbis_timesheets_update_entry_billability', $this->update_entry_billability( ... ) );
		\add_action( 'orbis_timesheets_update_project_billability', $this->update_project_billability( ... ) );
	}

	/**
	 * Schedule billability update of a timesheet entry.
	 *
	 * @param int $entry_id Timesheet entry ID.
	 * @return void
	 */
	public function schedule_entry_update( $entry_id ) {
		\as_enqueue_async_action(
			'orbis_timesheets_update_entry_billability',
			[
				'entry_id' => (int) $entry_id,
			],
			'orbis-timesheets',
			true
		);
	}

	/**
	 * Schedule billability update of the timesheet entries of a project.
	 *
	 * @param int $post_id Project post ID.
	 * @return void
	 */
	public function schedule_project_update( $post_id ) {
		global $wpdb;

		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$project_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $wpdb->orbis_projects WHERE post_id = %d;",
				$post_id
			)
		);

		if ( null === $project_id ) {
			return;
		}

		\as_enqueue_async_action(
			'orbis_timesheets_update_project_billability',
			[
				'project_id' => (int) $project_id,
			],
			'orbis-timesheets',
			true
		);
	}

	/**
	 * Update billability of a timesheet entry.
	 *
	 * @param int $entry_id Timesheet entry ID.
	 * @return void
	 * @throws Exception Throws an exception when the update fails, so Action Scheduler marks the action as failed.
	 */
	public function update_entry_billability( $entry_id ) {
		global $wpdb;

		$result = $this->updater->update_entry( (int) $entry_id );

		if ( false === $result ) {
			throw new Exception( \esc_html( \sprintf( 'Could not update billability of timesheet entry %d: %s', $entry_id, $wpdb->last_error ) ) );
		}
	}

	/**
	 * Update billability of the timesheet entries of a project.
	 *
	 * @param int $project_id Orbis project ID.
	 * @return void
	 * @throws Exception Throws an exception when the update fails, so Action Scheduler marks the action as failed.
	 */
	public function update_project_billability( $project_id ) {
		global $wpdb;

		$result = $this->updater->update_project( (int) $project_id );

		if ( false === $result ) {
			throw new Exception( \esc_html( \sprintf( 'Could not update billability of project %d timesheet entries: %s', $project_id, $wpdb->last_error ) ) );
		}
	}
}
