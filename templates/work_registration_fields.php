<?php

global $wpdb;

$project_value      = '';
$subscription_value = '';

$extra_select = '';
$extra_join   = '';

$project_query = "
	SELECT
		project.name AS project_name,
		project.number_seconds AS project_time
		$extra_select
	FROM
		$wpdb->orbis_projects AS project
		$extra_join
	WHERE
		project.finished = 0
			AND
		project.id = %s
";

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

$project_query = $wpdb->prepare( $project_query, $entry->project_id );

$project = $wpdb->get_row( $project_query );

if ( $project ) {
	$project_value = sprintf(
		'%s. %s - %s ( %s )',
		$entry->project_id,
		$project->principal_name,
		$project->project_name,
		orbis_time( $project->project_time )
	);
}

if ( orbis_plugin_activated( 'subscriptions' ) ) {
	$subscription_query = "
		SELECT
			subscription.id AS id,
			CONCAT( subscription.id, '. ', IFNULL( CONCAT( product.name, ' - ' ), '' ), subscription.name ) AS text
		FROM
			$wpdb->orbis_subscriptions AS subscription
				LEFT JOIN
			$wpdb->orbis_subscription_products AS product
					ON subscription.type_id = product.id
		WHERE
			subscription.cancel_date IS NULL
				AND
			subscription.id = %s
	";

	$subscription_query = $wpdb->prepare( $subscription_query, $entry->subscription_id );

	$subscription = $wpdb->get_row( $subscription_query );

	$subscription_value = ( $subscription ) ? $subscription->text : '';
}

?>

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
			<div <?php orbis_field_class( [ 'mb-3' ], 'orbis_registration_company_id' ); ?>>
				<label class="form-label"><?php _e( 'Company', 'orbis-timesheets' ); ?></label>

				<input class="form-control" placeholder="<?php esc_attr_e( 'Select company…', 'orbis-timesheets' ); ?>" type="text" name="orbis_registration_company_id" value="<?php echo esc_attr( $entry->company_id ); ?>" class="orbis-id-control orbis-company-id-control select-form-control" data-text="<?php echo esc_attr( $entry->company_name ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />
			</div>
		</div>

	<?php endif; ?>

	<?php if ( true ) : ?>

		<div class="col-md-6">
			<div <?php orbis_field_class( [ 'mb-3' ], 'orbis_registration_project_id' ); ?>>
				<label class="form-label"><?php _e( 'Project', 'orbis-timesheets' ); ?></label>

				<select name="orbis_registration_project_id" class="custom-select orbis-id-control orbis-project-id-control select-form-control" data-placeholder="<?php esc_attr_e( 'Select project…', 'orbis-timesheets' ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" autofocus>
					<option selected="selected" value="<?php echo esc_attr( $entry->project_id ); ?>">
						<?php echo esc_attr( $project_value ); ?>
					</option>
				</select>

			</div>
		</div>

	<?php endif; ?>

	<?php if ( orbis_plugin_activated( 'subscriptions' ) ) : ?>

		<div class="col-md-6">
			<div <?php orbis_field_class( [ 'mb-3' ], 'orbis_registration_subscription_id' ); ?>>
				<label class="form-label"><?php _e( 'Subscription', 'orbis-timesheets' ); ?></label>

				<select name="orbis_registration_subscription_id" class="custom-select orbis-id-control orbis-subscription-id-control select-form-control" data-placeholder="<?php esc_attr_e( 'Select subscription…', 'orbis-timesheets' ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>">
					<option selected="selected" value="<?php echo esc_attr( $entry->subscription_id ); ?>">
						<?php echo esc_attr( $subscription_value ); ?>
					</option>
				</select>
			</div>
		</div>

	<?php endif; ?>

	<div class="col-md-6">
		<div <?php orbis_field_class( [ 'mb-3' ], 'orbis_registration_activity_id' ); ?>>
			<label class="form-label"><?php _e( 'Activity', 'orbis-timesheets' ); ?></label>

			<select name="orbis_registration_activity_id" class="select2 select-form-control" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" data-placeholder="<?php esc_attr_e( 'Select activity…', 'orbis-timesheets' ); ?>" data-allow-clear="true" />
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
		<div <?php orbis_field_class( [ 'mb-3' ], 'orbis_registration_description' ); ?>>
			<label class="form-label"><?php _e( 'Description', 'orbis-timesheets' ); ?></label>

			<textarea placeholder="<?php esc_attr_e( 'Work registration description', 'orbis-timesheets' ); ?>" name="orbis_registration_description" class="input-lg" cols="60" rows="5"  tabindex="<?php echo esc_attr( $tabindex++ ); ?>"><?php echo esc_textarea( $entry->description ); ?></textarea>
		</div>
	</div>

	<div class="col-md-6">
		<div <?php orbis_field_class( [ 'mb-3', 'clearfix' ], 'orbis_registration_time' ); ?>>
			<label class="form-label"><?php _e( 'Time', 'orbis-timesheets' ); ?></label>

			<div class="row">
				<div class="col-md-4">
					<div class="input-group">
						<input class="form-control" size="2" type="text" name="orbis_registration_hours" value="<?php echo empty( $entry->time ) ? '' : esc_attr( orbis_time( $entry->time, 'H' ) ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />

						<span class="input-group-text"><?php _e( 'hours', 'orbis-timesheets' ); ?></span>
					</div>
				</div>

				<div class="col-md-4">
					<div class="input-group">
						<input class="form-control" size="2" type="text" name="orbis_registration_minutes" value="<?php echo empty( $entry->time ) ? '' : esc_attr( orbis_time( $entry->time, 'M' ) ); ?>" tabindex="<?php echo esc_attr( $tabindex++ ); ?>" />

						<span class="input-group-text"><?php _e( 'minutes', 'orbis-timesheets' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
