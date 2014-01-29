<?php

class Orbis_Timesheets_Email {

	/**
	 * @var Orbis_Timesheets_Plugin
	 */
	private $plugin;

	/**
	 * @param Orbis_Timesheets_Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'orbis_timesheets_emails', array( $this, 'send_timesheets_by_email' ) );
	}

	/**
	 * Sends an email containing this week's timesheets to all selected users.
	 */
	public function send_timesheets_by_email() {

		global $wpdb;

		$scheduled_execution_timestamp = time();

		$first_day_of_week_timestamp = strtotime( 'last monday midnight', $scheduled_execution_timestamp );

		$datetime1 = new DateTime( date( 'Y-m-d', $first_day_of_week_timestamp ) );
		$datetime2 = new DateTime( date( 'Y-m-d', $scheduled_execution_timestamp ) );
		$interval  = $datetime1->diff( $datetime2 );

		$days_between_first_day_of_week_and_today = $interval->format( '%a' );

		$dates = array();

		for ( $i = $days_between_first_day_of_week_and_today; $i >= 0; $i-- ) {

			$dates[ date( 'Y-m-d', strtotime( '- ' . $i . ' day', $scheduled_execution_timestamp ) ) ] = null;
		}

		$user_ids = filter_var( get_option( 'orbis_timesheets_email_users', array( -1 ) ), FILTER_VALIDATE_INT, array( 'flags' => FILTER_FORCE_ARRAY ) );

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
				user_id IN ( " . implode( ',', $user_ids ) . " )
			GROUP BY
				user_id, date
			ORDER BY
				user_id, date
			",
			date( 'Y-m-d', $first_day_of_week_timestamp ),
			date( 'Y-m-d', $scheduled_execution_timestamp )
		);

		$results = $wpdb->get_results( $query );

		$timesheets = array();
		
		foreach ( $user_ids as $user_id ) {
			$timesheets[ $user_id ] = $dates;
		}
		
		foreach ( $results as $result ) {
			$timesheets[ $result->user_id ][ $result->date ] = $result->number_seconds;
		}

		global $orbis_email_title;

		$orbis_email_title = __( 'Timesheets', 'orbis_timesheets' );
		
		$mail_to      = '';
		$mail_subject = get_option( 'orbis_timesheets_email_subject', __( 'Timesheets', 'orbis_subscriptions' ) );
		$mail_body  = $this->plugin->get_template( 'emails/user-timesheet.php', false, array(
			'dates'      => $dates,
			'timesheets' => $timesheets,
		) );
		$mail_headers = array(
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>',
			'Content-Type: text/html',
		);

		foreach ( $user_ids as $user_id ) {

			$user_email = get_the_author_meta( 'user_email', $user_id );

			if ( is_email( $user_email ) ) {
				$mail_to .= ' ' . $user_email . ', ';
			}
		}

		wp_mail( $mail_to, $mail_subject, $mail_body, $mail_headers );
	}
}
