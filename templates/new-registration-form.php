<?php 

wp_enqueue_script( 'orbis-autocomplete' );
wp_enqueue_style( 'select2' );

// Errors
global $orbis_errors;

$orbis_errors = array();

// Inputs
$entry = new Orbis_Timesheets_TimesheetEntry();

$entry->company_id      = filter_input( INPUT_POST, 'orbis_registration_company_id', FILTER_SANITIZE_STRING );
$entry->project_id      = filter_input( INPUT_POST, 'orbis_registration_project_id', FILTER_SANITIZE_STRING );
$entry->subscription_id = filter_input( INPUT_POST, 'orbis_registration_subscription_id', FILTER_SANITIZE_STRING );
$entry->activity_id     = filter_input( INPUT_POST, 'orbis_registration_activity_id', FILTER_SANITIZE_STRING );
$entry->description     = filter_input( INPUT_POST, 'orbis_registration_description', FILTER_SANITIZE_STRING );
$entry->date            = date( 'Y-m-d' );
$entry->time            = orbis_filter_time_input( INPUT_POST, 'orbis_registration_time' );
$entry->user_id         = get_current_user_id();
$entry->person_id       = get_user_meta( $user_id, 'orbis_legacy_person_id', true );

// Add
if ( filter_has_var( INPUT_POST, 'orbis_timesheets_add_registration' ) ) {
	// Verify nonce
	$nonce = filter_input( INPUT_POST, 'orbis_timesheets_new_registration_nonce', FILTER_SANITIZE_STRING );
	if ( wp_verify_nonce( $nonce, 'orbis_timesheets_add_new_registration' ) ) {
		if ( empty( $entry->company_id ) && empty( $entry->project_id ) && empty( $entry->subscription_id ) ) {
			$orbis_errors['orbis_registration_company_id']      = __( 'You have to specify an company.', 'orbis_timesheets' );
			$orbis_errors['orbis_registration_project_id']      = __( 'You have to specify an project.', 'orbis_timesheets' );
			$orbis_errors['orbis_registration_subscription_id'] = __( 'You have to specify an subscription.', 'orbis_timesheets' );
		}
		
		$required_word_count = 2;
		if ( str_word_count( $entry->description ) < $required_word_count ) {
			$orbis_errors['orbis_registration_description'] = sprintf( __( 'You have to specify an description (%d words).', 'orbis_timesheets' ), $required_word_count );
		}

		if ( empty( $entry->time ) ) {
			// $orbis_errors['orbis_registration_time'] = __( 'You have to specify an time.', 'orbis_timesheets' );
		}
		
		if ( empty( $entry->person_id ) ) {
			$orbis_errors['orbis_registration_person_id'] = sprintf(
				__( 'Who are you? <a href="%s">Edit your user profile</a> and enter you Orbis legacy person ID.', 'orbis_timesheets' ),
				esc_attr( get_edit_user_link( $user_id ) )
			);
		}

		if ( empty( $entry->activity_id ) ) {
			$orbis_errors['orbis_registration_activity_id'] = __( 'You have to specify an activity.', 'orbis_timesheets' );
		}
		
		if ( ! orbis_timesheets_can_register( $timestamp ) ) {
			$orbis_errors['orbis_registration_date'] = __( 'You can not register on this date.', 'orbis_timesheets' );
		}

		if ( empty( $orbis_errors ) ) {
			$result = orbis_insert_timesheet_entry( $entry );
		}
	}
}

function orbis_field_class( $class = array(), $field_id ) {
	global $orbis_errors;
	
	if ( isset( $orbis_errors[ $field_id ] ) ) {
		$class[] = 'error';
	}
	
	printf( 'class="%s"', implode( ' ', $class ) ); 
}

global $wpdb;

$query = "
	SELECT
		*
	FROM
		$wpdb->orbis_activities
	ORDER BY
		name
	;		
";

$activities = $wpdb->get_results( $query );

?>
<div class="panel">
	<div class="content">
		<form action="" method="post">
			<?php wp_nonce_field( 'orbis_timesheets_add_new_registration', 'orbis_timesheets_new_registration_nonce' ); ?>

			<legend>Add registration</legend>

			<?php include 'work_registration_fields.php'; ?>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="orbis_timesheets_add_registration">Register</button>
			</div>
		</form>
	</div>
</div>