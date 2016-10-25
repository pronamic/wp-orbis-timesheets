<?php

class Orbis_Timesheets_Email {
	/**
	 * Plugin.
	 *
	 * @var Orbis_Timesheets_Plugin
	 */
	private $plugin;

	/**
	 * Constructs and intialize e-mail object.
	 *
	 * @param Orbis_Timesheets_Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'orbis_email_top', array( $this, 'email_top' ) );
	}

	/**
	 * Sends an email containing this week's timesheets to all selected users.
	 */
	public function email_top() {
		global $wpdb;

		$now = time();

		$monday = strtotime( 'last monday midnight', $now );

		$datetime1 = new DateTime( date( 'Y-m-d', $monday ) );
		$datetime2 = new DateTime( date( 'Y-m-d', $now ) );
		$interval  = $datetime1->diff( $datetime2 );

		$days = $interval->format( '%a' );

		$dates = array();

		for ( $i = $days; $i >= 0; $i-- ) {
			$dates[ date( 'Y-m-d', strtotime( '- ' . $i . ' day', $now ) ) ] = null;
		}

		$user_ids = get_users( array(
			'fields'     => 'ids',
			'meta_key'   => '_orbis_user',
			'meta_value' => 'true',
		) );

		$query_user_ids = implode( ',', $user_ids );

		$query = $wpdb->prepare( "
			SELECT
				user_id,
				SUM( number_seconds ) AS number_seconds,
				date
			FROM
				$wpdb->orbis_timesheets
			WHERE
				( date BETWEEN %s AND %s )
					AND
				user_id IN ( $query_user_ids )
			GROUP BY
				user_id, date
			ORDER BY
				user_id, date
			",
			date( 'Y-m-d', $monday ),
			date( 'Y-m-d', $now )
		);

		$results = $wpdb->get_results( $query );

		$timesheets = array();

		foreach ( $user_ids as $user_id ) {
			$timesheets[ $user_id ] = $dates;
		}

		foreach ( $results as $result ) {
			$timesheets[ $result->user_id ][ $result->date ] = $result->number_seconds;
		}

		$this->plugin->get_template( 'emails/user-timesheet.php', true, array(
			'dates'      => $dates,
			'timesheets' => $timesheets,
		) );
	}
}
