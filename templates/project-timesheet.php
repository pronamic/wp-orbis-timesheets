<?php

global $wpdb;

$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->orbis_projects WHERE post_id = %d;", get_the_ID() ) );

$query = $wpdb->prepare( "
	SELECT
		registration.id,
		registration.user_id,
		user.display_name AS user_display_name,
		activity.name AS activity_name,
		registration.description,
		registration.date,
		registration.number_seconds
	FROM
		$wpdb->orbis_timesheets AS registration
			LEFT JOIN
		$wpdb->users AS user
				ON registration.user_id = user.ID
			LEFT JOIN
		$wpdb->orbis_activities AS activity
				ON registration.activity_id = activity.id
	WHERE
		project_id = %d
	ORDER BY
		registration.date ASC, registration.id
	;",
	$id
);

$registrations = $wpdb->get_results( $query );

if ( $registrations ) : ?>

	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th scope="col"><?php _e( 'Date', 'orbis_timesheets' ); ?></th>
				<th scope="col"><?php _e( 'User', 'orbis_timesheets' ); ?></th>
				<th scope="col"><?php _e( 'Activity', 'orbis_timesheets' ); ?></th>
				<th scope="col"><?php _e( 'Description', 'orbis_timesheets' ); ?></th>
				<th scope="col"><?php _e( 'Time', 'orbis_timesheets' ); ?></th>
			</tr>
		</thead>

		<tbody>
			
			<?php foreach ( $registrations as $registration ) : ?>

				<tr>
					<td>
						<?php echo date_i18n( 'D j M Y', strtotime( $registration->date ) ); ?>
					</td>
					<td>
						<?php echo $registration->user_display_name; ?>
					</td>
					<td>
						<?php echo $registration->activity_name; ?>
					</td>
					<td>
						<?php echo $registration->description; ?>
					</td>
					<td>
						<?php echo orbis_time( $registration->number_seconds ); ?>
					</td>
				</tr>
			
			<?php endforeach; ?>

		</tbody>
	</table>

<?php else : ?>

	<div class="content">
		<p class="alt">
			<?php _e( 'There are no time registrations for this project.', 'orbis_timesheets' ); ?>
		</p>
	</div>

<?php endif; ?>
