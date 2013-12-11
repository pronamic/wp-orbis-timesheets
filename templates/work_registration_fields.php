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
		<input placeholder="Select company" type="text" name="orbis_registration_company_id" value="<?php echo esc_attr( $entry->company_id ); ?>" class="orbis-id-control orbis-company-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $entry->company_id ); ?>" />
	 </div>

	 <div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_project_id' ); ?>>
	 	<label>Project</label>
		<input placeholder="Select project" type="text" name="orbis_registration_project_id" value="<?php echo esc_attr( $entry->project_id ); ?>" class="orbis-id-control orbis-project-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $entry->project_id ); ?>" />
	 </div>

	 <div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_subscription_id' ); ?>>
	 	<label>Subscription</label>
		<input placeholder="Select subscription" type="text" name="orbis_registration_subscription_id" value="<?php echo esc_attr( $entry->subscription_id ); ?>" />
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
				selected( $activity->id, $entry->activity_id, false ),
				esc_html( $activity->name )
			);
		}

		?>
	</select>
 </div>

<div class="form-line clearfix">
	<div <?php orbis_field_class( array( 'control-group', 'col' ), 'orbis_registration_description' ); ?> style="float: left; margin-right: 20px;">
		<label>Description</label>
		<input placeholder="Task description" class="input-xxlarge" name="orbis_registration_description" value="<?php echo esc_attr( $entry->description ); ?>" style="font-size: 18px; padding: 12px;" type="text">
	</div>

	<div <?php orbis_field_class( array( 'control-group', 'col' ), 'orbis_registration_time' ); ?> style="width: 40%; float: left;">
		<label>Time</label>
		<input placeholder="00:00" class="input-mini" style="font-size: 18px; padding: 12px;" type="text" name="orbis_registration_time" value="<?php echo esc_attr( orbis_time( $entry->time ) ); ?>" />
	</div>
</div>