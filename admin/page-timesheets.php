<div class="wrap">
	<h2><?php echo get_admin_page_title(); ?></h2>

	<?php

	global $wpdb;

	$extra_select = '';
	$extra_join   = '';

	if ( property_exists( $wpdb, 'orbis_companies' ) ) {
		$extra_select .= '
		, company.id AS company_id,
		company.post_id AS company_post_id,
		company.name AS company_name,
		principal.name AS principal_name
		';

		$extra_join .= "
		LEFT JOIN
			$wpdb->orbis_companies AS principal
				ON project.principal_id = principal.id
		LEFT JOIN
			$wpdb->orbis_companies AS company
				ON work.company_id = company.id
		";
	}

	$query = "
		SELECT
			work.id AS work_id,
			work.created AS work_created,
			work.description AS work_description,
			work.date AS work_date,
			work.number_seconds AS work_duration,
			user.ID AS user_id,
			user.display_name AS user_display_name,
			activity.id AS activity_id,
			activity.name AS activity_name,
			activity.description AS activity_description,
			project.id AS project_id,
			project.post_id AS project_post_id,
			project.number_seconds AS project_time_available,
			project.name AS project_name
			$extra_select
		FROM
			$wpdb->orbis_timesheets AS work
				LEFT JOIN
			$wpdb->users AS user
					ON work.user_id = user.ID
				LEFT JOIN
			$wpdb->orbis_activities AS activity
					ON work.activity_id = activity.id
				LEFT JOIN
			$wpdb->orbis_projects AS project
					ON work.project_id = project.id
				$extra_join
		ORDER BY
			work.`date` DESC
		LIMIT
			0, %d
		;
	";

	$query = $wpdb->prepare( $query, 100 );

	$registrations = $wpdb->get_results( $query );

	?>
	<div class="subsubsub"></div>

	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th scope="col">
					<?php _e( 'Registered On', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'User', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'Company/Project', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'Activity', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'Description', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'Date', 'orbis-timesheets' ); ?>
				</th>
				<th scope="col">
					<?php _e( 'Time', 'orbis-timesheets' ); ?>
				</th>
			</tr>
		</thead>

		<tbody>

			<?php foreach ( $registrations as $registration ) : ?>

				<tr>
					<td>
						<?php echo $registration->work_created; ?>
					</td>
					<td>
						<?php echo $registration->user_display_name; ?>
					</td>
					<td>
						<?php

						$links = [];

						if ( ! empty( $registration->company_post_id ) ) {
							$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( orbis_post_link( $registration->company_post_id ) ), esc_html( $registration->company_name ) );
						}

						if ( ! empty( $registration->project_post_id ) ) {
							$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( orbis_post_link( $registration->project_post_id ) ), esc_html( $registration->project_name ) );
						}

						echo implode( ' - ', $links );

						?>
					</td>
					<td>
						<?php echo $registration->activity_name; ?>
					</td>
					<td>
						<?php echo $registration->work_description; ?>
					</td>
					<td>
						<?php echo $registration->work_date; ?>
					</td>
					<td>
						<?php echo orbis_time( $registration->work_duration ); ?>
					</td>
				</tr>

			<?php endforeach; ?>

		</tbody>
	</table>
</div>
