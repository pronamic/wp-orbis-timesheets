<?php

global $wpdb;

$id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM orbis_projects WHERE post_id = %d;', get_the_ID() ) );

$query = $wpdb->prepare( "
	SELECT
		hr.id,
		p.first_name AS person_name,
		a.name AS activity_name,
		hr.description,
		hr.date,
		hr.number_seconds
	FROM
		orbis_hours_registration AS hr
			LEFT JOIN
		orbis_persons AS p
				ON hr.user_id = p.id
			LEFT JOIN
		orbis_activities AS a
				ON hr.activity_id = a.id 
	WHERE
		project_id = %d
	ORDER BY
		hr.date ASC, hr.id
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
						<?php echo $registration->person_name; ?>
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