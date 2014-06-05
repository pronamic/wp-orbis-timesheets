<?php

global $wpdb;

$query = "
	SELECT 
		work.id AS work_id,
		work.description AS work_description,
		work.date AS work_date, 
		work.number_seconds AS work_duration,
		activity.id AS activity_id,
		activity.name AS activity_name,
		activity.description AS activity_description,
		company.id AS company_id,
		company.post_id AS company_post_id,
		company.name AS company_name,
		project.id AS project_id,
		project.post_id AS project_post_id,
		project.number_seconds AS project_time_available,
		project.name AS project_name, 
		principal.name AS principal_name
	FROM 
		$wpdb->orbis_timesheets AS work
			LEFT JOIN
		$wpdb->orbis_activities AS activity
				ON work.activity_id = activity.id
			LEFT JOIN
		$wpdb->orbis_companies AS company
				ON work.company_id = company.id
			LEFT JOIN
		$wpdb->orbis_projects AS project
				ON work.project_id = project.id
			LEFT JOIN
		$wpdb->orbis_companies AS principal 
				ON project.principal_id = principal.id
	WHERE 
		work.user_id = %d
			AND 
		work.`date` = %s
	ORDER BY 
		work.`date` DESC
	;
";

$query = $wpdb->prepare( $query, $user_id, date( 'Y-m-d', $timestamp ) );

$registrations = $wpdb->get_results( $query );

$prev      = strtotime( '-1 day', $timestamp );
$next      = strtotime( '+1 day', $timestamp );

$url = add_query_arg( 'message', false );

?>
<form class="form-inline" action="" method="get">
	<div class="btn-group">
		<a href="<?php echo add_query_arg( 'date', date( 'Y-m-d', $prev ), $url ); ?>" class="btn btn-default">&lt;</a>
		<a href="<?php echo add_query_arg( 'date', date( 'Y-m-d', $next ), $url ); ?>" class="btn btn-default">&gt;</a>
		<a href="<?php echo add_query_arg( 'date', false, $url ); ?>" class="btn btn-default"><?php _e( 'Today', 'orbis_timesheets' ); ?></a>
	</div>
</form>

<hr />

<h2><?php echo date_i18n( 'D j M Y', $timestamp ); ?></h2>

<?php if ( filter_has_var( INPUT_GET, 'message' ) ) : ?>

	<div class="alert alert-success">
		<?php 
		
		$message = filter_input( INPUT_GET, 'message' );
		
		switch ( $message ) { 
			case 'added':
				_e( 'Your work registration was succesfull added.', 'orbis_timesheets' );
			
				break;
			case 'updated':		
				_e( 'Your work registration was succesfull updated.', 'orbis_timesheets' );
				
				break;
		}
		
		?>
	</div>

<?php endif; ?>

<?php if ( empty( $registrations ) ) : ?>



<?php else : ?>

	<?php 

	$total = 0;
	foreach ( $registrations as $registration ) {
		$total += $registration->work_duration;
	}
	
	?>

	<div class="panel">
		<table class="table table-striped table-bordered table-condense">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Company/Project', 'orbis_timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Activity', 'orbis_timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Description', 'orbis_timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Date', 'orbis_timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Time', 'orbis_timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Actions', 'orbis_timesheets' ); ?></th>
				</tr>
			</thead>
			
			<tfoot>
				<tr>
					<td colspan="4">
						
					</td>
					<td>
						<strong><?php echo orbis_time( $total ); ?></strong>
					</td>
					<td>
						
					</td>
				</tr>
			</tfoot>

			<tbody>

				<?php foreach( $registrations as $registration ) : ?>

					<tr>
						<td>
							<?php 

							$links = array();

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
						<td>
							<a href="<?php echo get_edit_orbis_work_registration_link( $registration->work_id ); ?>"><span class="glyphicon glyphicon-pencil"></span> <span style="display: none"><?php _e( 'Edit', 'orbis_timesheets' ); ?></span></a>
						</td>
					</tr>
	
				<?php endforeach; ?>
	
			</tbody>
		</table>
	</div>

<?php endif; ?>

<?php include 'new-registration-form.php'; ?>
