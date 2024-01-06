<?php

function orbis_timesheets_the_entry_description( $description ) {
	$description = apply_filters( 'orbis_timesheets_entry_description', $description );

	echo $description;
}

// @see https://github.com/WordPress/WordPress/blob/4.6.1/wp-includes/default-filters.php#L153-L158
add_filter( 'orbis_timesheets_entry_description', 'wptexturize' );
add_filter( 'orbis_timesheets_entry_description', 'convert_chars' );
add_filter( 'orbis_timesheets_entry_description', 'make_clickable', 9 );
add_filter( 'orbis_timesheets_entry_description', 'force_balance_tags', 25 );
add_filter( 'orbis_timesheets_entry_description', 'convert_smilies', 20 );

function orbis_post_link( $post_id ) {
	return add_query_arg( 'p', $post_id, home_url( '/' ) );
}

function orbis_field_class( $class = [], $field_id ) {
	global $orbis_errors;

	if ( isset( $orbis_errors[ $field_id ] ) ) {
		$class[] = 'error';
	}

	printf( 'class="%s"', implode( ' ', $class ) );
}

function orbis_timesheets_can_register( $timestamp ) {
	$can_register = true;

	$limit_lower = get_option( 'orbis_timesheets_registration_limit_lower' );

	if ( ! empty( $limit_lower ) ) {
		$dateline_lower = strtotime( sprintf( 'midnight -%s +10 hours', $limit_lower ) );

		$can_register = ( $timestamp >= $dateline_lower );
	}

	return $can_register;
}

function get_edit_orbis_work_registration_link( $entry_id ) {
	$link = add_query_arg(
		[
			'entry_id' => $entry_id,
			'action'   => 'edit',
		],
		get_permalink() 
	);

	return $link;
}

function orbis_update_project_registered_time( $project_id, $post_id ) {
	$registered_time = orbis_calculate_project_registered_time( $project_id );

	update_post_meta( $post_id, '_orbis_project_registered_time', $registered_time );
}

function orbis_calculate_project_registered_time( $project_id ) {
	global $wpdb;

	$query = "
		SELECT
			SUM( number_seconds ) AS logged_time
		FROM
			$wpdb->orbis_timesheets
		WHERE
			project_id = %d
	";

	$registered_time = $wpdb->get_var( $wpdb->prepare( $query, $project_id ) );

	return $registered_time;
}

function orbis_timesheets_get_entry( $entry_id ) {
	global $wpdb;

	$entry = false;

	// Query
	$select = '';
	$from   = '';

	if ( function_exists( 'orbis_subscriptions_bootstrap' ) ) {
		$select = ",
			CONCAT( subscription_product.name, ' - ', subscription.name ) AS subscription_name
		";

		$from = "
				LEFT JOIN
			$wpdb->orbis_subscriptions AS subscription
					ON timesheet.subscription_id = subscription.id
				LEFT JOIN
			$wpdb->orbis_subscription_products AS subscription_product
					ON subscription.type_id = subscription_product.id
		";
	}

	$query = "
		SELECT
			timesheet.id,
			timesheet.company_id,
			timesheet.project_id,
			timesheet.subscription_id,
			timesheet.activity_id,
			timesheet.description,
			timesheet.date,
			timesheet.number_seconds,
			company.name AS company_name,
			project.name AS project_name
			$select
		FROM
			$wpdb->orbis_timesheets AS timesheet
				LEFT JOIN
			$wpdb->orbis_companies AS company
					ON timesheet.company_id = company.id
				LEFT JOIN
			$wpdb->orbis_projects AS project
					ON timesheet.project_id = project.id
			$from
		WHERE
			timesheet.id = %d
		;
	";

	$query = $wpdb->prepare( $query, $entry_id );

	// Row
	$row = $wpdb->get_row( $query );

	if ( $row ) {
		$entry = new Orbis_Timesheets_TimesheetEntry();

		$entry->id = $row->id;

		$entry->company_id   = $row->company_id;
		$entry->company_name = $row->company_name;

		$entry->project_id   = $row->project_id;
		$entry->project_name = $row->project_name;

		$entry->subscription_id   = $row->subscription_id;
		$entry->subscription_name = $row->subscription_name;

		$entry->activity_id = $row->activity_id;
		$entry->description = $row->description;

		$entry->set_date( new DateTime( $row->date ) );

		$entry->time = $row->number_seconds;
	}

	return $entry;
}

function orbis_insert_timesheet_entry( $entry ) {
	global $wpdb;

	$result = false;

	// Auto complete company ID
	if ( ! empty( $entry->project_id ) ) {
		$query = $wpdb->prepare( "SELECT principal_id FROM $wpdb->orbis_projects WHERE id = %d;", $entry->project_id );

		$entry->company_id = $wpdb->get_var( $query );
	}

	if ( ! empty( $entry->subscription_id ) ) {
		$query = $wpdb->prepare( "SELECT company_id FROM $wpdb->orbis_subscriptions WHERE id = %d;", $entry->subscription_id );

		$entry->company_id = $wpdb->get_var( $query );
	}

	// Data
	$data   = [];
	$format = [];

	$data['created']   = date( 'Y-m-d H:i:s' );
	$format['created'] = '%s';

	$data['user_id']   = $entry->user_id;
	$format['user_id'] = '%d';

	$data['company_id']   = $entry->company_id;
	$format['company_id'] = '%d';

	if ( ! empty( $entry->project_id ) ) {
		$data['project_id']   = $entry->project_id;
		$format['project_id'] = '%d';
	}

	if ( ! empty( $entry->subscription_id ) ) {
		$data['subscription_id']   = $entry->subscription_id;
		$format['subscription_id'] = '%d';
	}

	$data['activity_id']   = $entry->activity_id;
	$format['activity_id'] = '%d';

	$data['description']   = $entry->description;
	$format['description'] = '%s';

	$data['date']   = $entry->get_date()->format( 'Y-m-d' );
	$format['date'] = '%s';

	$data['number_seconds']   = $entry->time;
	$format['number_seconds'] = '%d';

	$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->orbis_projects WHERE id = %d;", $data['project_id'] );

	$project_post_id = $wpdb->get_var( $query );

	if ( empty( $entry->id ) ) {
		// Insert
		$result = $wpdb->insert(
			$wpdb->orbis_timesheets,
			$data,
			$format
		);

		if ( $result ) {
			$entry->id = $wpdb->insert_id;
		}
	} else {
		// Update
		$result = $wpdb->update(
			$wpdb->orbis_timesheets,
			$data,
			[ 'id' => $entry->id ],
			$format,
			[ 'id' => '%d' ]
		);
	}

	orbis_update_project_registered_time( $data['project_id'], $project_post_id );

	return $result;
}

function orbis_timesheets_get_company_name( $orbis_id ) {
	global $wpdb;

	$query = $wpdb->prepare(
		"
		SELECT
			CONCAT( company.id, '. ', company.name )
		FROM
			$wpdb->orbis_companies AS company
		WHERE
			company.id = %d
		;
	",
		$orbis_id 
	);

	$result = $wpdb->get_var( $query );

	return $result;
}

function orbis_timesheets_get_project_name( $orbis_id ) {
	global $wpdb;

	$name = $orbis_id;

	$extra_select = '';
	$extra_join   = '';

	if ( orbis_plugin_activated( 'companies' ) ) {
		$extra_select .= '
		, principal.name AS principal_name
		';

		$extra_join .= "
		LEFT JOIN
			$wpdb->orbis_companies AS principal
				ON project.principal_id = principal.id
		";
	}

	// Query
	$query = $wpdb->prepare(
		"
		SELECT
			project.id AS project_id,
			project.name AS project_name,
			project.number_seconds AS project_time,
			SUM( entry.number_seconds ) AS project_logged_time
			$extra_select
		FROM
			$wpdb->orbis_projects AS project
				LEFT JOIN
			$wpdb->orbis_timesheets AS entry
					ON entry.project_id = project.id
				$extra_join
		WHERE
			project.id = %d
		;
	",
		$orbis_id 
	);

	// Project
	$result = $wpdb->get_row( $query );

	$principal_name = ( isset( $result->principal_name ) ) ? '- ' . $result->principal_name : '';

	if ( $result ) {
		$name = sprintf(
			'%s. %s %s ( %s / %s )',
			$result->project_id,
			$principal_name,
			$result->project_name,
			orbis_time( $result->project_logged_time ),
			orbis_time( $result->project_time )
		);
	}

	return $name;
}

function orbis_timesheets_get_subscription_name( $orbis_id ) {
	$name = null;

	if ( function_exists( 'orbis_subscriptions_bootstrap' ) ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT
				CONCAT( subscription.id, '. ', product.name, ' - ', subscription.name ) AS name
			FROM
				$wpdb->orbis_subscriptions AS subscription
					LEFT JOIN
				$wpdb->orbis_subscription_products AS product
					ON subscription.type_id = product.id
			WHERE
				subscription.id = %d
			;
		",
			$orbis_id 
		);

		$name = $wpdb->get_var( $query );
	}

	return $name;
}

function orbis_timesheets_get_entry_from_input( $type = INPUT_POST ) {
	$entry = new Orbis_Timesheets_TimesheetEntry();

	$entry->id = filter_input( $type, 'orbis_registration_id', FILTER_SANITIZE_STRING );

	if ( orbis_plugin_activated( 'companies' ) ) {
		$entry->company_id   = filter_input( $type, 'orbis_registration_company_id', FILTER_SANITIZE_STRING );
		$entry->company_name = orbis_timesheets_get_company_name( $entry->company_id );
	}

	$entry->project_id   = filter_input( $type, 'orbis_registration_project_id', FILTER_SANITIZE_STRING );
	$entry->project_name = orbis_timesheets_get_project_name( $entry->project_id );

	$entry->subscription_id   = filter_input( $type, 'orbis_registration_subscription_id', FILTER_SANITIZE_STRING );
	$entry->subscription_name = orbis_timesheets_get_subscription_name( $entry->subscription_id );

	$entry->activity_id = filter_input( $type, 'orbis_registration_activity_id', FILTER_SANITIZE_STRING );
	$entry->description = filter_input( $type, 'orbis_registration_description', FILTER_SANITIZE_STRING );

	$date_string = filter_input( $type, 'orbis_registration_date', FILTER_SANITIZE_STRING );
	if ( ! empty( $date_string ) ) {
		$entry->set_date( new DateTime( $date_string ) );
	}

	if ( filter_has_var( $type, 'orbis_registration_time' ) ) {
		$entry->time = orbis_filter_time_input( $type, 'orbis_registration_time' );
	}

	if ( filter_has_var( $type, 'orbis_registration_hours' ) ) {
		$time = 0;

		$hours   = filter_input( $type, 'orbis_registration_hours', FILTER_VALIDATE_INT );
		$minutes = filter_input( $type, 'orbis_registration_minutes', FILTER_VALIDATE_INT );

		$time += $hours * 3600;
		$time += $minutes * 60;

		$entry->time = $time;
	}

	$entry->user_id = get_current_user_id();

	return $entry;
}

function orbis_timesheets_maybe_add_entry() {
	global $orbis_errors;

	// Add
	if ( filter_has_var( INPUT_POST, 'orbis_timesheets_add_registration' ) ) {
		$entry = orbis_timesheets_get_entry_from_input();

		// Verify nonce
		$nonce = filter_input( INPUT_POST, 'orbis_timesheets_new_registration_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, 'orbis_timesheets_add_new_registration' ) ) {
			if ( empty( $entry->company_id ) && empty( $entry->project_id ) && empty( $entry->subscription_id ) ) {
				orbis_timesheets_register_error( 'orbis_registration_company_id', '' ); // __( 'You have to specify an company.', 'orbis_timesheets' ) );
				orbis_timesheets_register_error( 'orbis_registration_project_id', '' ); // __( 'You have to specify an project.', 'orbis_timesheets' ) );
				orbis_timesheets_register_error( 'orbis_registration_subscription_id', '' ); // __( 'You have to specify an subscription.', 'orbis_timesheets' ) );

				orbis_timesheets_register_error( 'orbis_registration_on', __( 'You have to specify an company or project.', 'orbis-timesheets' ) );
			}

			if ( empty( $entry->project_id ) ) {
				orbis_timesheets_register_error( 'orbis_registration_project_id', '' ); // __( 'You have to specify an project.', 'orbis_timesheets' ) );

				orbis_timesheets_register_error( 'orbis_registration_on', __( 'You have to specify an project.', 'orbis-timesheets' ) );
			}

			$required_word_count = 2;
			if ( str_word_count( $entry->description ) < $required_word_count ) {
				orbis_timesheets_register_error( 'orbis_registration_description', sprintf( __( 'You have to specify an description (%d words).', 'orbis-timesheets' ), $required_word_count ) ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			}

			if ( empty( $entry->activity_id ) ) {
				orbis_timesheets_register_error( 'orbis_registration_activity_id', __( 'You have to specify an activity.', 'orbis-timesheets' ) );
			}

			if ( ! orbis_timesheets_can_register( $entry->get_date()->format( 'U' ) ) ) {
				orbis_timesheets_register_error( 'orbis_registration_date', __( 'You can not register on this date.', 'orbis-timesheets' ) );
			}

			$message = empty( $entry->id ) ? 'added' : 'updated';

			if ( empty( $orbis_errors ) ) {
				$result = orbis_insert_timesheet_entry( $entry );

				if ( $result ) {
					$url = add_query_arg(
						[
							'entry_id' => false,
							'action'   => false,
							'message'  => $message,
							'date'     => $entry->get_date()->format( 'Y-m-d' ),
						] 
					);

					wp_safe_redirect( $url );

					exit;
				} else {
					orbis_timesheets_register_error( 'orbis_registration_error', __( 'Could not add timesheet entry.', 'orbis-timesheets' ) );
				}
			}
		}
	}
}

add_action( 'template_redirect', 'orbis_timesheets_maybe_add_entry' );

function orbis_timesheets_init() {
	// Errors
	global $orbis_errors;

	$orbis_errors = [];
}

add_action( 'init', 'orbis_timesheets_init', 1 );

function orbis_timesheets_register_error( $name, $error ) {
	// Errors
	global $orbis_errors;

	$orbis_errors[ $name ] = $error;

	return $orbis_errors;
}
