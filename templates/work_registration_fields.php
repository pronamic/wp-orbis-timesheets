<?php if ( ! empty( $orbis_errors ) ) : ?>

	<div class="alert alert-danger">
		<p>
			<?php

			echo implode( '<br />', array_filter( $orbis_errors ) );

			?>
		</p>
	</div>

<?php endif; ?>

<?php $tabindex = 2; ?>

<input name="orbis_registration_id" value="<?php echo esc_attr( $entry->id ); ?>" type="hidden" />
<input name="orbis_registration_date" value="<?php echo $entry->get_date()->format( 'Y-m-d' ); ?>" type="hidden" />

<div class="row">
	<?php if ( false ) : ?>

		<div class="col-md-6">
			<div <?php orbis_field_class( array( 'form-group' ), 'orbis_registration_company_id' ); ?>>
				<label><?php _e( 'Company', 'orbis_timesheets' ); ?></label>
				<input class="form-control" placeholder="<?php esc_attr_e( 'Select company…', 'orbis_timesheets' ); ?>" type="text" name="orbis_registration_company_id" value="<?php echo esc_attr( $entry->company_id ); ?>" class="orbis-id-control orbis-company-id-control select-form-control" data-text="<?php echo esc_attr( $entry->company_name ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
			</div>
		</div>

	<?php endif; ?>

	<?php if ( true ) : ?>

		<div class="col-md-6">
			<div <?php orbis_field_class( array( 'form-group' ), 'orbis_registration_project_id' ); ?>>
				<label><?php _e( 'Project', 'orbis_timesheets' ); ?></label>

				<select class="select2 select-form-control" name="orbis_registration_project_id" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" autofocus="autofocus">
					<option value=""><?php esc_html_e( 'Select project…', 'orbis_timesheets' ); ?></option>
					<?php

					$query = new WP_Query( array(
						'post_type'                 => 'orbis_project',
						'nopaging'                  => true,
						'orbis_project_is_finished' => false,
					) );

					if ( $query->have_posts() ) {
						while ( $query->have_posts() ) {
							$query->the_post();

							$post = get_post();

							$orbis_id = get_post_meta( get_the_ID(), '_orbis_project_id', true );

							$text = sprintf(
								'%s. %s - %s ( %s )',
								$orbis_id,
								$post->principal_name,
								get_the_title(),
								orbis_time( $post->project_number_seconds )
							);

							if ( isset( $project->project_logged_time ) ) {
								$text = sprintf(
									'%s. %s - %s ( %s / %s )',
									$orbis_id,
									$post->principal_name,
									get_the_title(),
									orbis_time( $post->project_number_seconds ),
									orbis_time( $post->project_logged_time )
								);
							}

							printf(
								'<option value="%s" %s>%s</a>',
								esc_attr( $orbis_id ),
								selected( $orbis_id, $entry->project_id, false ),
								esc_html( $text ) 
							);
						}
					}

					?>
				</select>
			</div>
		</div>

	<?php endif; ?>

	<?php if ( function_exists( 'orbis_subscriptions_bootstrap' ) ) : ?>

		<div class="col-md-6">
			<div <?php orbis_field_class( array( 'form-group' ), 'orbis_registration_subscription_id' ); ?>>
				<label><?php _e( 'Subscription', 'orbis_timesheets' ); ?></label>
				<input placeholder="<?php esc_attr_e( 'Select subscription…', 'orbis_timesheets' ); ?>" type="text" name="orbis_registration_subscription_id" value="<?php echo esc_attr( $entry->subscription_id ); ?>" class="orbis-id-control orbis-subscription-id-control select-form-control" data-text="<?php echo esc_attr( $entry->subscription_name ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
			</div>
		</div>

	<?php endif; ?>

	<div class="col-md-6">
		<div <?php orbis_field_class( array( 'form-group' ), 'orbis_registration_activity_id' ); ?>>
			<label><?php _e( 'Activity', 'orbis_timesheets' ); ?></label>
			<select name="orbis_registration_activity_id" class="select2 select-form-control" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
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
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div <?php orbis_field_class( array( 'form-group' ), 'orbis_registration_description' ); ?>>
			<label><?php _e( 'Description', 'orbis_timesheets' ); ?></label>
			<textarea placeholder="<?php esc_attr_e( 'Work registration description', 'orbis_timesheets' ); ?>" name="orbis_registration_description" class="input-lg" cols="60" rows="5"  tabindex="<?php echo esc_attr( $tabindex++ ); ?>"><?php echo esc_textarea( $entry->description ); ?></textarea>
		</div>
	</div>

	<div class="col-md-6">
		<div <?php orbis_field_class( array( 'form-group', 'clearfix' ), 'orbis_registration_time' ); ?>>
			<label class="form-label"><?php _e( 'Time', 'orbis_timesheets' ); ?></label>

			<div class="row">
				<div class="col-md-4">
					<div class="input-group">
						<input class="form-control" size="2" type="text" name="orbis_registration_hours" value="<?php echo empty( $entry->time ) ? '' : esc_attr( orbis_time( $entry->time, 'H' ) ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
						<span class="input-group-addon"><?php _e( 'hours', 'orbis_timesheets' ); ?></span>
					</div>
				</div>

				<div class="col-md-4">
					<div class="input-group">
						<input class="form-control" size="2" type="text" name="orbis_registration_minutes" value="<?php echo empty( $entry->time ) ? '' : esc_attr( orbis_time( $entry->time, 'M' ) ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
						<span class="input-group-addon"><?php _e( 'minutes', 'orbis_timesheets' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
