<?php if ( ! empty( $orbis_errors ) ) : ?>

	<div class="alert alert-error">
		<p>
			<?php 
			
			echo implode( '<br />', array_filter( $orbis_errors ) ); 
			
			?>
		</p>
	</div>

<?php endif; ?>

<input name="orbis_registration_id" value="<?php echo esc_attr( $entry->id ); ?>" type="hidden" />
<input name="orbis_registration_date" value="<?php echo $entry->get_date()->format( 'Y-m-d' ); ?>" type="hidden" />

<div class="row">
	<div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_company_id' ); ?>>
		<label><?php _e( 'Company', 'orbis_timesheets' ); ?></label>
		<input placeholder="Select company" type="text" name="orbis_registration_company_id" value="<?php echo esc_attr( $entry->company_id ); ?>" class="orbis-id-control orbis-company-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $entry->company_id ); ?>" tabindex="0" />
	</div>

	<div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_project_id' ); ?>>
		<label><?php _e( 'Project', 'orbis_timesheets' ); ?></label>
		<input placeholder="Select project" type="text" name="orbis_registration_project_id" value="<?php echo esc_attr( $entry->project_id ); ?>" class="orbis-id-control orbis-project-id-control"  style="width: 200px;" data-text="<?php echo esc_attr( $entry->project_id ); ?>" />
	</div>

	<?php if ( false ) : ?>
	
		<div <?php orbis_field_class( array( 'control-group', 'span3' ), 'orbis_registration_subscription_id' ); ?>>
			<label><?php _e( 'Subscription', 'orbis_timesheets' ); ?></label>
			<input placeholder="Select subscription" type="text" name="orbis_registration_subscription_id" value="<?php echo esc_attr( $entry->subscription_id ); ?>" />
		</div>

	<?php endif; ?>
</div>

 <div <?php orbis_field_class( array( 'control-group' ), 'orbis_registration_activity_id' ); ?>>
 	<label><?php _e( 'Activity', 'orbis_timesheets' ); ?></label>
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
		<label><?php _e( 'Description', 'orbis_timesheets' ); ?></label>
		<textarea placeholder="<?php esc_attr_e( 'Work registration description', 'orbis_timesheets' ); ?>" name="orbis_registration_description" style="font-size: 18px; padding: 12px;" class="input-xxlarge" cols="60" rows="5"><?php echo esc_textarea( $entry->description ); ?></textarea>
	</div>

	<div <?php orbis_field_class( array( 'control-group', 'col' ), 'orbis_registration_time' ); ?> style="width: 40%; float: left;">
		<label><?php _e( 'Time', 'orbis_timesheets' ); ?></label>
		<input placeholder="00:00" class="input-mini" style="font-size: 18px; padding: 12px;" type="text" name="orbis_registration_time" value="<?php echo empty( $entry->time ) ? '' : esc_attr( orbis_time( $entry->time ) ); ?>" />
	</div>
</div>