<?php

class Orbis_Timesheets_Email {

	/**
	 * @var Orbis_Timesheets_Plugin
	 */
	private $plugin;

	/**
	 * @var string
	 */
	public $cron_hook = 'orbis_timesheets_send_timesheets_by_email';

	/**
	 * @const string
	 */
	const SCHEDULED_EXECUTION_TIMESTAMP_OPTION_KEY = 'orbis_timesheets_scheduled_execution_timestamp';

	/**
	 * @param Orbis_Timesheets_Plugin $plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		add_action( 'init', array( $this, 'schedule_send_timesheets_by_email' ) );

		add_action( $this->cron_hook, array( $this, 'send_timesheets_by_email' ) );
	}

	/**
	 * Sets a sets a cronjob for executing the send_timesheets_by_email method.
	 *
	 * @param int|bool $base_timestamp (optional, defaults to false)
	 */
	public function schedule_send_timesheets_by_email( $base_timestamp = false ) {

		if ( ! get_option( 'orbis_timesheets_email_send_automatically', false ) ||
			 is_numeric( wp_next_scheduled( $this->cron_hook ) ) ) {

			return;
		}

		if ( ! is_numeric( $base_timestamp ) ) {
			$base_timestamp = time();
		}

		$frequency = get_option( 'orbis_timesheets_email_frequency', 'daily' );

		switch ( $frequency ) {

			case 'daily' :

				$timestamp = strtotime( 'today 23:59:59', $base_timestamp );

				break;

			default : // weekly

				$timestamp = strtotime( 'next sunday 23:59:59', $base_timestamp );

				break;
		}

		wp_schedule_single_event( $timestamp, $this->cron_hook );

		// The scheduled execution timestamp is passed through the options table as this the most accurate way of passing
		// the variable without passing it as argument. The variable isn't passed as argument because it would disable
		// us from checking if the cron hook is already set. The wp_next_scheduled() function takes its arguments into account.
		update_option( self::SCHEDULED_EXECUTION_TIMESTAMP_OPTION_KEY, $timestamp );
	}

	/**
	 * Sends an email containing this week's timesheets to all selected users.
	 */
	public function send_timesheets_by_email() {

		global $wpdb;

		$scheduled_execution_timestamp = get_option( self::SCHEDULED_EXECUTION_TIMESTAMP_OPTION_KEY, time() );

		// Reschedule
		$this->schedule_send_timesheets_by_email( strtotime( 'next day midnight', $scheduled_execution_timestamp ) );

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
			date( 'Y-m-d', $first_day_of_week_timestamp ),
			date( 'Y-m-d', $scheduled_execution_timestamp )
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
			'templates/emails/user-timesheet.php',
			array(
				'dates'                  => $dates,
				'users_registered_hours' => $users_registered_hours,
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
	}
}
