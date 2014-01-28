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

		add_action( 'admin_init', array( $this, 'maybe_email_timesheets' ) );
	}

	/**
	 *
	 */
	public function maybe_email_timesheets() {

		global $wpdb;

		if ( ! get_option( 'orbis_timesheets_email_send_automatically' ) ) {
			return;
		}

		$frequency            = get_option( 'orbis_timesheets_email_frequency', '1 week' );
		$last_email_timestamp = get_option( 'orbis_timesheets_last_email_timestamp', time() );

		switch ( $frequency ) {

			case '1 day':

				$next_email_timestamp = strtotime( '23:59:59', $last_email_timestamp );

				break;

			default: // '1 week'

				$day = get_option( 'orbis_timesheets_email_frequency_day', 'sunday' );

				$next_email_timestamp = strtotime( 'next ' . $day . ' midnight', $last_email_timestamp );

				break;
		}

		if ( $next_email_timestamp > time() ) {
			return;
		}

		$datetime1 = new DateTime( date( 'Y-m-d', $last_email_timestamp ) );
		$datetime2 = new DateTime( date( 'Y-m-d', $next_email_timestamp ) );
		$interval  = $datetime1->diff( $datetime2 );

		$days_between_last_and_next = $interval->format( '%a' );

		$dates = array();

		for ( $i = $days_between_last_and_next; $i >= 0; $i-- ) {

			$dates[ date( 'Y-m-d', strtotime( '- ' . $i . ' day', $next_email_timestamp ) ) ] = null;
		}

		$user_ids = filter_var( get_option( 'orbis_timesheets_email_users', array( -1 ) ), FILTER_VALIDATE_INT, array( 'flags' => FILTER_FORCE_ARRAY ) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT user_id,
			       SUM( number_seconds ) AS number_seconds,
			       date
			FROM wp_orbis_hours_registration
			WHERE (date BETWEEN %s AND %s ) AND
				  user_id IN ( " . implode( ',', $user_ids ) . " )
			GROUP BY user_id, date
			ORDER BY user_id, date
			",
			date( 'Y-m-d', $last_email_timestamp ),
			date( 'Y-m-d', $next_email_timestamp )
		) );

		$users_registered_hours = array();

		foreach ( $results as $result ) {

			if ( ! isset( $users_registered_hours[ $result->user_id ] ) ) {

				$users_registered_hours[ $result->user_id ] = $dates;
			}

			$users_registered_hours[ $result->user_id ][ $result->date ] = $result->number_seconds;
		}

		ob_start();

		$this->plugin->plugin_include(
			'templates/user-timesheet.php',
			array(
				'dates'                  => $dates,
				'users_registered_hours' => $users_registered_hours,
				'last_email_timestamp'   => $last_email_timestamp,
				'next_email_timestamp'   => $next_email_timestamp,
			)
		);

		$mail_to      = '';
		$mail_subject = get_option( 'orbis_timesheets_email_subject', __( 'Timesheets', 'orbis_subscriptions' ) );
		$mail_body    = ob_get_clean();
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

		update_option( 'orbis_timesheets_last_email_timestamp', $next_email_timestamp );
	}
}
