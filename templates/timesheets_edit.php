<?php 

wp_enqueue_script( 'orbis-autocomplete' );
wp_enqueue_style( 'select2' );

// Errors
global $orbis_errors;

$orbis_errors = array();

// Inputs
$company_id      = filter_input( INPUT_POST, 'orbis_registration_company_id', FILTER_SANITIZE_STRING );
$project_id      = filter_input( INPUT_POST, 'orbis_registration_project_id', FILTER_SANITIZE_STRING );
$subscription_id = filter_input( INPUT_POST, 'orbis_registration_subscription_id', FILTER_SANITIZE_STRING );
$activity_id     = filter_input( INPUT_POST, 'orbis_registration_activity_id', FILTER_SANITIZE_STRING );
$description     = filter_input( INPUT_POST, 'orbis_registration_description', FILTER_SANITIZE_STRING );
$date            = date( 'Y-m-d' );
$time            = orbis_filter_time_input( INPUT_POST, 'orbis_registration_time' );
$user_id         = get_current_user_id();
$person_id       = get_user_meta( $user_id, 'orbis_legacy_person_id', true );

// Add
if ( filter_has_var( INPUT_POST, 'orbis_timesheets_add_registration' ) ) {
	// Verify nonce
	$nonce = filter_input( INPUT_POST, 'orbis_timesheets_new_registration_nonce', FILTER_SANITIZE_STRING );
	if ( wp_verify_nonce( $nonce, 'orbis_timesheets_add_new_registration' ) ) {
		if ( empty( $company_id ) && empty( $project_id ) && empty( $subscription_id ) ) {
			$orbis_errors['orbis_registration_company_id']      = __( 'You have to specify an company.', 'orbis_timesheets' );
			$orbis_errors['orbis_registration_project_id']      = __( 'You have to specify an project.', 'orbis_timesheets' );
			$orbis_errors['orbis_registration_subscription_id'] = __( 'You have to specify an subscription.', 'orbis_timesheets' );
		}
		
		$required_word_count = 2;
		if ( str_word_count( $description ) < $required_word_count ) {
			$orbis_errors['orbis_registration_description'] = sprintf( __( 'You have to specify an description (%d words).', 'orbis_timesheets' ), $required_word_count );
		}

		if ( empty( $time ) ) {
			// $orbis_errors['orbis_registration_time'] = __( 'You have to specify an time.', 'orbis_timesheets' );
		}
		
		if ( empty( $person_id ) ) {
			$orbis_errors['orbis_registration_person_id'] = sprintf(
				__( 'Who are you? <a href="%s">Edit your user profile</a> and enter you Orbis legacy person ID.', 'orbis_timesheets' ),
				esc_attr( get_edit_user_link( $user_id ) )
			);
		}

		if ( empty( $activity_id ) ) {
			$orbis_errors['orbis_registration_activity_id'] = __( 'You have to specify an activity.', 'orbis_timesheets' );
		}
		
		if ( ! orbis_timesheets_can_register( $timestamp ) ) {
			$orbis_errors['orbis_registration_date'] = __( 'You can not register on this date.', 'orbis_timesheets' );
		}

		if ( empty( $orbis_errors ) ) {
			$data   = array();
			$format = array();
			
			$data['created']   = date( 'Y-m-d H:i:s' );
			$format['created'] = '%s';
			
			$data['user_id']   = $person_id;
			$format['user_id'] = '%d';
			
			$data['company_id']   = $company_id;
			$format['company_id'] = '%d';
			
			if ( ! empty( $project_id ) ) {
				$data['project_id']   = $project_id;
				$format['project_id'] = '%d';
			}
			
			$data['activity_id']   = $activity_id;
			$format['activity_id'] = '%d';
			
			$data['description']   = $description;
			$format['description'] = '%s';
			
			$data['date']   = $date;
			$format['date'] = '%s';
			
			$data['number_seconds']   = $time;
			$format['number_seconds'] = '%d';

			$wpdb->show_errors();
			$result = $wpdb->insert( $wpdb->orbis_timesheets, $data, $format );
			
			if ( $result !== false ) {
				var_dump( $result );
			} else {
				$wpdb->print_error();
			}
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

			<legend>Edit registration</legend>

			<?php include 'work_registration_fields.php'; ?>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="orbis_timesheets_add_registration">Edit</button>
				
				<?php 

				$cancel_url = add_query_arg( array(
					'work_registration' => false,
					'action'            => false,
				) );

				?>
				<a class="btn" href="<?php echo esc_attr( $cancel_url ); ?>">Cancel</a>
			</div>
		</form>
	</div>
</div>