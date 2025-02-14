<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$extra_select = '';
$extra_join   = '';

if ( isset( $wpdb->orbis_companies ) ) {
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

if ( isset( $wpdb->orbis_subscriptions ) ) {
	$extra_select .= ',
		subscription.id AS subscription_id,
		subscription.name AS subscription_name,
		subscription.post_id AS subscription_post_id
	';

	$extra_join .= "
		LEFT JOIN
			$wpdb->orbis_subscriptions AS subscription
				ON work.subscription_id = subscription.id
	";
}

$query = "
	SELECT
		work.id AS work_id,
		work.description AS work_description,
		work.date AS work_date,
		work.number_seconds AS work_duration,
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
		$wpdb->orbis_activities AS activity
				ON work.activity_id = activity.id
			LEFT JOIN
		$wpdb->orbis_projects AS project
				ON work.project_id = project.id
			$extra_join
	WHERE
		work.user_id = %d
			AND
		work.`date` = %s
	ORDER BY
		work.`date` DESC,
		work.created ASC
	;
";

$query = $wpdb->prepare( $query, $user_id, date( 'Y-m-d', $timestamp ) );

$registrations = $wpdb->get_results( $query );

$prev = strtotime( '-1 day', $timestamp );
$next = strtotime( '+1 day', $timestamp );

$url = add_query_arg( 'message', false );

?>
<form class="form-inline" action="" method="get">
	<div class="btn-group" role="group">
		<a href="<?php echo add_query_arg( 'date', date( 'Y-m-d', $prev ), $url ); ?>" class="btn btn-secondary">‹</a>
		<a href="<?php echo add_query_arg( 'date', date( 'Y-m-d', $next ), $url ); ?>" class="btn btn-secondary">›</a>
		<a href="<?php echo add_query_arg( 'date', false, $url ); ?>" class="btn btn-secondary"><?php _e( 'Today', 'orbis-timesheets' ); ?></a>
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
				_e( 'Your work registration was succesfully added.', 'orbis-timesheets' );

				break;
			case 'updated':
				_e( 'Your work registration was succesfully updated.', 'orbis-timesheets' );

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

	<div class="card mb-3">
		<table class="table table-striped mb-0">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Company/Project', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Activity', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Description', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Date', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Time', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php _e( 'Actions', 'orbis-timesheets' ); ?></th>
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

				<?php foreach ( $registrations as $registration ) : ?>

					<tr>
						<td>
							<?php

							$links = [];

							if ( ! empty( $registration->company_post_id ) ) {
								$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( orbis_post_link( $registration->company_post_id ) ), esc_html( $registration->company_name ) );
							}

							if ( ! empty( $registration->project_post_id ) ) {
								$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( orbis_post_link( $registration->project_post_id ) ), esc_html( $registration->project_name ) );
							}

							if ( ! empty( $registration->subscription_post_id ) ) {
								$links[] = sprintf( '<a href="%s">%s</a>', esc_attr( orbis_post_link( $registration->subscription_post_id ) ), esc_html( $registration->subscription_name ) );
							}

							echo implode( ' - ', $links );

							?>
						</td>
						<td>
							<?php echo $registration->activity_name; ?>
						</td>
						<td>
							<?php orbis_timesheets_the_entry_description( $registration->work_description ); ?>
						</td>
						<td>
							<?php echo $registration->work_date; ?>
						</td>
						<td>
							<?php echo orbis_time( $registration->work_duration ); ?>
						</td>
						<td>
							<?php

							$link = \add_query_arg(
								[
									'entry_id' => $registration->work_id,
									'action'   => 'edit',
								],
								\home_url( 'tijdregistraties/registreren' )
							);

							?>
							<a href="<?php echo \esc_url( $link ); ?>"><i class="fas fa-edit" aria-hidden="true"></i> <span style="display: none"><?php _e( 'Edit', 'orbis-timesheets' ); ?></span></a>
						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>

<?php endif; ?>

<div class="mb-3">
	<?php require 'new-registration-form.php'; ?>
</div>

<div class="card">
	<div class="card-header">
		Jaaroverzicht
	</div>

	<div class="card-body">
		<?php

		$current_user = wp_get_current_user();

		$selected = new DateTime( '@' . $timestamp );

		$report = get_orbis_timesheets_annual_report(
			[
				'user' => $current_user->user_login,
			]
		);

		require __DIR__ . '/time-tracking-annual-overview-style.php';

		foreach ( $report->users as $user ) {
			include __DIR__ . '/time-tracking-annual-overview-table.php';
		}

		require __DIR__ . '/time-tracking-annual-overview-script.php';

		?>
	</div>
</div>
