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

			<legend>Add registration</legend>

			<?php if ( orbis_timesheets_can_register( $timestamp ) ) : ?>
			
				<div class="alert alert-success">
					<?php _e( 'Yippee, you can register hours on this date.', 'orbis_timesheets' ); ?>
				</div>
			
			<?php else : ?>
			
				<div class="alert alert-warning">
					<?php _e( 'Bummer, you can not register hours on this date.', 'orbis_timesheets' ); ?>
				</div>
			
			<?php endif; ?>

			<?php if ( ! empty( $orbis_errors ) ) : ?>

				<div class="alert alert-error">
					<p>
						<?php echo implode( '<br />', $orbis_errors ); ?>
					</p>
				</div>

			<?php endif; ?>

			 <div <?php orbis_field_class( array( 'control-group' ), 'orbis_registration_date' ); ?>>
			 	<label><?php _e( 'Date', 'orbis_timesheets' ) ?></label>
				<input name="orbis_registration_date" value="<?php echo date( 'Y-m-d', $timestamp ); ?>" type="text" readonly="readonly" />
			 </div>

			<div class="row">
				 <div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_company_id' ); ?>>
				 	<label>Company</label>
					<input placeholder="Select company" type="text" name="orbis_registration_company_id" value="<?php echo esc_attr( $company_id ); ?>" class="orbis-id-control orbis-company-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $company_id ); ?>" />
				 </div>

				 <div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_project_id' ); ?>>
				 	<label>Project</label>
					<input placeholder="Select project" type="text" name="orbis_registration_project_id" value="<?php echo esc_attr( $project_id ); ?>" class="orbis-id-control orbis-project-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $project_id ); ?>" />
				 </div>

				 <div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_subscription_id' ); ?>>
				 	<label>Subscription</label>
					<input placeholder="Select subscription" type="text" name="orbis_registration_subscription_id" value="<?php echo esc_attr( $subscription_id ); ?>" />
				 </div>
			</div>

			 <div <?php orbis_field_class( array( 'control-group' ), 'orbis_registration_activity_id' ); ?>>
			 	<label><?php _e( 'Activity', 'orbis_timesheets' ) ?></label>
				<select name="orbis_registration_activity_id" class="select2" style="width: 200px;">
					<option value=""></option>
					<?php 
					
					foreach ( $activities as $activity ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $activity->id ),
							selected( $activity->id, $activity_id, false ),
							esc_html( $activity->name )
						);
					}

					?>
				</select>
			 </div>

			<div class="form-line clearfix">
				<div <?php orbis_field_class( array( 'control-group', 'col' ), 'orbis_registration_description' ); ?> style="float: left; margin-right: 20px;">
					<label>Description</label>
					<input placeholder="Task description" class="input-xxlarge" name="orbis_registration_description" value="<?php echo esc_attr( $description ); ?>" style="font-size: 18px; padding: 12px;" type="text">
				</div>

				<div <?php orbis_field_class( array( 'control-group', 'col' ), 'orbis_registration_time' ); ?> style="width: 40%; float: left;">
					<label>Time</label>
					<input placeholder="00:00" class="input-mini" style="font-size: 18px; padding: 12px;" type="text" name="orbis_registration_time" value="<?php echo esc_attr( orbis_time( $time ) ); ?>" />
				</div>
			</div>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="orbis_timesheets_add_registration">Register</button>
				<button type="button" class="btn" data-toggle="collapse" data-target="#demo">Cancel</button>
			</div>
		</form>
	</div>
</div>