<?php

use Pronamic\Orbis\Timesheets\Declarability;

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
		work.declarability AS declarability,
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

$selected_day = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
$week_start   = $selected_day->modify( 'monday this week' )->setTime( 0, 0 );
$week_end     = $week_start->modify( '+6 days' );

$query = "
	SELECT
		`date`,
		SUM( number_seconds ) AS duration
	FROM
		$wpdb->orbis_timesheets
	WHERE
		user_id = %d
			AND
		`date` BETWEEN %s AND %s
	GROUP BY
		`date`
	;
";

$query = $wpdb->prepare(
	$query,
	$user_id,
	$week_start->format( 'Y-m-d' ),
	$week_end->format( 'Y-m-d' )
);

$week_durations = $wpdb->get_results( $query, OBJECT_K );

$schedule_table = $wpdb->prefix . 'orbis_timesheets_schedule';

$query = "
	SELECT
		`date`,
		number_seconds AS duration
	FROM
		$schedule_table
	WHERE
		user_id = %d
			AND
		`date` BETWEEN %s AND %s
	;
";

$query = $wpdb->prepare(
	$query,
	$user_id,
	$week_start->format( 'Y-m-d' ),
	$week_end->format( 'Y-m-d' )
);

$week_schedule_durations = $wpdb->get_results( $query, OBJECT_K );
$week_total              = 0;
$week_schedule           = 0;
$days                    = [];


$week = new DatePeriod(
	$week_start,
	new DateInterval( 'P1D' ),
	$week_end,
	DatePeriod::INCLUDE_END_DATE
);

foreach ( $week as $day ) {
	$day_date = $day->format( 'Y-m-d' );
	$duration = isset( $week_durations[ $day_date ] ) ? (int) $week_durations[ $day_date ]->duration : 0;
	$schedule = isset( $week_schedule_durations[ $day_date ] ) ? (int) $week_schedule_durations[ $day_date ]->duration : 0;

	$days[] = [
		'date'     => $day_date,
		'duration' => $duration,
		'schedule' => $schedule,
		'label'    => wp_date( 'D j M', $day->getTimestamp() ),
	];

	$week_total    += $duration;
	$week_schedule += $schedule;
}

$prev = $selected_day->modify( '-1 week' );
$next = $selected_day->modify( '+1 week' );

$url = add_query_arg( 'message', false );

?>
<nav class="d-flex align-items-stretch gap-3 mb-4" aria-label="<?php esc_attr_e( 'Week navigation', 'orbis-timesheets' ); ?>">
	<div class="d-flex flex-grow-1 align-items-stretch overflow-auto">
		<ul class="pagination flex-nowrap mb-0">
			<li class="page-item">
				<a class="page-link h-100 d-flex align-items-center" href="<?php echo esc_url( add_query_arg( 'date', $prev->format( 'Y-m-d' ), $url ) ); ?>">
					<span class="visually-hidden"><?php esc_html_e( 'Previous week', 'orbis-timesheets' ); ?></span>
					<i class="fas fa-angle-left" aria-hidden="true"></i>
				</a>
			</li>

			<?php foreach ( $days as $day ) : ?>

				<?php $is_selected = $selected_day->format( 'Y-m-d' ) === $day['date']; ?>

				<li class="page-item <?php echo $is_selected ? 'active' : ''; ?>">
					<a class="page-link text-center text-nowrap px-4 py-2" href="<?php echo esc_url( add_query_arg( 'date', $day['date'], $url ) ); ?>"<?php echo $is_selected ? ' aria-current="page"' : ''; ?>>
						<span class="d-block fw-bold"><?php echo esc_html( $day['label'] ); ?></span>
						<span class="d-block mt-1"><?php echo esc_html( orbis_time( $day['duration'] ) ); ?> / <?php echo esc_html( orbis_time( $day['schedule'] ) ); ?></span>
					</a>
				</li>

			<?php endforeach; ?>

			<li class="page-item">
				<a class="page-link h-100 d-flex align-items-center" href="<?php echo esc_url( add_query_arg( 'date', $next->format( 'Y-m-d' ), $url ) ); ?>">
					<span class="visually-hidden"><?php esc_html_e( 'Next week', 'orbis-timesheets' ); ?></span>
					<i class="fas fa-angle-right" aria-hidden="true"></i>
				</a>
			</li>
		</ul>

		<div class="border rounded ms-3 px-3 py-2 text-center text-nowrap d-flex flex-column justify-content-center">
			<strong class="d-block"><?php esc_html_e( 'Week total', 'orbis-timesheets' ); ?></strong>
			<span><?php echo esc_html( orbis_time( $week_total ) ); ?> / <?php echo esc_html( orbis_time( $week_schedule ) ); ?></span>
		</div>
	</div>

	<a class="btn btn-secondary align-self-start ms-auto" href="<?php echo esc_url( add_query_arg( 'date', false, $url ) ); ?>"><?php esc_html_e( 'Today', 'orbis-timesheets' ); ?></a>
</nav>

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
					<th scope="col"><?php esc_html_e( 'Declarability', 'orbis-timesheets' ); ?></th>
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
						<?php foreach ( Declarability::sum_seconds( $registrations, 'work_duration' ) as $declarability_total ) : ?>

							<div class="text-nowrap">
								<?php echo wp_kses_post( $declarability_total->declarability->badge() ); ?>
								<?php echo esc_html( orbis_time( $declarability_total->seconds ) ); ?>
							</div>

						<?php endforeach; ?>
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
							<?php echo wp_kses_post( Declarability::from_value( $registration->declarability )->badge() ); ?>
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
