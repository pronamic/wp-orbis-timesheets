<?php
/**
 * Report template
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Globals
global $wpdb;

// Functions
function orbis_format_timestamps( array $timestamps, $format ) {
	$dates = [];
	
	foreach ( $timestamps as $key => $value ) {
		$dates[ $key ] = date( $format, $value );
	}
	
	return $dates;
}

// This week
$week_this = [
	'start_date' => strtotime( 'sunday this week -1 week' ),
	'end_date'   => strtotime( 'sunday this week' ),
];

// Start date
$value = filter_input( INPUT_GET, 'start_date', FILTER_SANITIZE_STRING );
if ( empty( $value ) ) {
	$start_date = $week_this['start_date'];
} else {
	$start_date = strtotime( $value );
}

// End date
$value = filter_input( INPUT_GET, 'end_date', FILTER_SANITIZE_STRING );
if ( empty( $value ) ) {
	$end_date = $week_this['end_date'];
} else {
	$end_date = strtotime( $value );
}

// Step
$step = max( $end_date - $start_date, ( 3600 * 12 ) );

$previous = [
	'start_date' => $start_date - $step,
	'end_date'   => $end_date - $step,
];

$next = [
	'start_date' => $start_date + $step,
	'end_date'   => $end_date + $step,
];

// Inputs
$user = filter_input( INPUT_GET, 'user', FILTER_SANITIZE_STRING );

// Build query
$query = 'WHERE 1 = 1';

if ( $start_date ) {
	$query .= $wpdb->prepare( ' AND date >= %s', date( 'Y-m-d', $start_date ) );
}

if ( $end_date ) {
	$query .= $wpdb->prepare( ' AND date <= %s', date( 'Y-m-d', $end_date ) );
}

if ( $user ) {
	$query .= $wpdb->prepare( ' AND user_id = %d', $user );
}

$query .= ' ORDER BY date ASC';

// Get results
$query_budgets = $wpdb->prepare(
	"SELECT
		project.id,
		project.number_seconds - IFNULL( SUM( registration.number_seconds ), 0 ) AS seconds_available,
		project.invoicable 
	FROM
		$wpdb->orbis_projects AS project
			LEFT JOIN
		$wpdb->orbis_timesheets AS registration
				ON (
					project.id = registration.project_id
						AND
					registration.date <= %s
				)
	GROUP BY
		project.id
	",
	date( 'Y-m-d', $start_date )
);

$budgets = $wpdb->get_results( $query_budgets, OBJECT_K );

$query_hours = "
	SELECT
		hr.id AS registration_id,
		project.id AS project_id,
		project.name AS project_name,
		project.post_id AS project_post_id,
		client.id AS client_id,
		client.name AS client_name,
		client.post_id AS client_post_id,
		user.display_name AS user_name,
		hr.date AS date,
		hr.description AS description,
		hr.number_seconds AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS hr
			LEFT JOIN
		$wpdb->orbis_companies AS client
				ON hr.company_id = client.id
			LEFT JOIN
		$wpdb->orbis_projects AS project
				ON hr.project_id = project.id
			LEFT JOIN
		$wpdb->users AS user
				ON hr.user_id = user.ID
	$query
";

$result = $wpdb->get_results( $query_hours );

$total_seconds      = 0;
$billable_seconds   = 0;
$unbillable_seconds = 0;

foreach ( $result as $row ) {
	$row->billable_seconds   = 0;
	$row->unbillable_seconds = 0;
	
	if ( isset( $budgets[ $row->project_id ] ) ) {
		$project = $budgets[ $row->project_id ];
		
		if ( $project->invoicable ) {
			if ( $row->number_seconds < $project->seconds_available ) {
				// 1800 seconds registred < 3600 seconds available
				$row->billable_seconds = $row->number_seconds;
			} else {
				// 3600 seconds registred < 1800 seconds available
				$seconds_avilable = max( 0, $project->seconds_available );

				$row->billable_seconds   = $seconds_avilable;
				$row->unbillable_seconds = $row->number_seconds - $seconds_avilable;
			}
		} else {
			$row->unbillable_seconds = $row->number_seconds;
		}

		$project->seconds_available -= $row->number_seconds;
	} else {
		$row->unbillable_seconds = $row->number_seconds;
	}
	
	$total_seconds      += $row->number_seconds;
	$billable_seconds   += $row->billable_seconds;
	$unbillable_seconds += $row->unbillable_seconds;
}

$unbillable_hours = $unbillable_seconds / 60 / 60;
$billable_hours   = $billable_seconds / 60 / 60;
$total_hours      = $total_seconds / 60 / 60;

if ( $total_seconds > 0 ) {
	$total = $billable_seconds / $total_seconds * 100;
} else {
	$total = 0;
}

$amount = $billable_hours * 75;

$declarability_report = $wpdb->get_row(
	$wpdb->prepare(
		"
		SELECT
			time_registration.user_id,
			SUM( time_registration.number_seconds ) AS total_seconds,
			SUM( IF ( time_registration.declarability IN ( 'chargeable', 'non_chargeable' ), time_registration.number_seconds, 0 ) ) AS declarability_seconds,
			SUM( IF ( 'chargeable' = time_registration.declarability, time_registration.number_seconds, 0 ) ) AS chargeable_seconds,
			SUM( IF ( 'non_chargeable' = time_registration.declarability, time_registration.number_seconds, 0 ) ) AS non_chargeable_seconds,
			SUM( IF ( time_registration.declarability IN ( 'chargeable', 'non_chargeable' ), 0, time_registration.number_seconds ) ) AS other_seconds
		FROM
			wp_orbis_timesheets AS time_registration
		WHERE
			time_registration.date >= %s
				AND
			time_registration.date <= %s
				AND
			time_registration.user_id = %d
		GROUP BY
			time_registration.user_id
		;
		",
		date( 'Y-m-d', $start_date ),
		date( 'Y-m-d', $end_date ),
		$user
	)
);

// URL's
$url_week_this = add_query_arg( orbis_format_timestamps( $week_this, 'd-m-Y' ) );
$url_previous  = add_query_arg( orbis_format_timestamps( $previous, 'd-m-Y' ) );
$url_next      = add_query_arg( orbis_format_timestamps( $next, 'd-m-Y' ) );

?>
<form method="get" action="">
	<div class="d-flex justify-content-between bd-highlight mb-3">
		<div>
			<div class="btn-group">
				<a class="btn btn-secondary" href="<?php echo $url_previous; ?>"><?php echo esc_html( _x( '<', 'previous', 'orbis-timesheets' ) ); ?></a>
				<a class="btn btn-secondary" href="<?php echo $url_next; ?>"><?php echo esc_html( _x( '>', 'next', 'orbis-timesheets' ) ); ?></a>
				<a class="btn btn-secondary" href="<?php echo $url_week_this; ?>"><?php echo esc_html( __( 'This week', 'orbis-timesheets' ) ); ?></a>
			</div>
		</div>

		<div class="form-inline">
			<div class="form-group">
				<span>
				<?php

				printf(
					__( 'View report from %1$s to %2$s', 'orbis-timesheets' ),
					sprintf(
						'<input type="text" name="start_date" class="form-control input-small" placeholder="0000-00-00" value="%s" />',
						esc_attr( date( 'd-m-Y', $start_date ) )
					),
					sprintf(
						'<input type="text" name="end_date" class="form-control input-small" placeholder="0000-00-00" value="%s" />',
						esc_attr( date( 'd-m-Y', $end_date ) )
					)
				);

				echo ' ';

				printf(
					'<button type="submit" class="btn btn-secondary">%s</button>',
					esc_html__( 'Filter', 'orbis-timesheets' )
				);

				?>
				</span>
			</div>
		</div>

		<div class="form-inline">
			<span>
			<?php

			$users = get_users(
				[
					'fields'     => 'ids',
					'meta_key'   => '_orbis_user',
					'meta_value' => 'true',
				] 
			);

			wp_dropdown_users(
				[
					'name'             => 'user',
					'selected'         => filter_input( INPUT_GET, 'user', FILTER_SANITIZE_STRING ),
					'show_option_none' => __( '— All users —', 'orbis-timesheets' ),
					'class'            => 'form-control',
					'include'          => $users,
				] 
			);

			?>

			<button type="submit" class="btn btn-secondary"><?php esc_html_e( 'Filter', 'orbis-timesheets' ); ?></button></span>
		</div>
	</div>
</form>

<hr>

<div class="row">
	<div class="col-md-12">
		<p>
			<?php

			if ( $declarability_report && $declarability_report->declarability_seconds  > 0 ) {
				$total = ( $declarability_report->chargeable_seconds / $declarability_report->declarability_seconds ) * 100;
			}

			printf(
				__( '%s of the hours are billable', 'orbis-timesheets' ),
				'<span style="font-size: 2.5rem">' . esc_html( '' . round( $total ) . '%' ) . '</span>'
			);

			?>
		</p>

		<div class="progress progress-striped active">
			<div class="progress-bar" role="progressbar" aria-valuenow="<?php echo round( $total ); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo round( $total ) . '%'; ?>;">
				<span class="sr-only"><?php printf( __( '%s Complete', 'orbis-timesheets' ), '' . round( $total ) . '%' ); ?></span>
			</div>
		</div>
	</div>
</div>

<?php if ( $declarability_report ) : ?>

	<div class="row">
		<div class="col-md-3">
			<p><?php _e( 'Total', 'orbis-timesheets' ); ?></p>
			<h1><?php echo orbis_time( $declarability_report->total_seconds ); ?></h1>
		</div>

		<div class="col-md-3">
			<p><?php echo \esc_html( _x( 'Chargeable', 'declarability', 'orbis-timesheets' ) ); ?></p>
			<h1><?php echo orbis_time( $declarability_report->chargeable_seconds ); ?></h1>
		</div>

		<div class="col-md-3">
			<p><?php echo \esc_html( _x( 'Non-chargeable', 'declarability', 'orbis-timesheets' ) ); ?></p>
			<h1><?php echo orbis_time( $declarability_report->non_chargeable_seconds ); ?></h1>
		</div>

		<div class="col-md-3">
			<p><?php echo \esc_html( _x( 'Other', 'declarability', 'orbis-timesheets' ) ); ?></p>
			<h1><?php echo orbis_time( $declarability_report->other_seconds ); ?></h1>
		</div>
	</div>

<?php endif; ?>

<hr />

<table class="table table-striped table-bordered panel">
	<thead>
		<tr>
			<th><?php _e( 'User', 'orbis-timesheets' ); ?></th>
			<th><?php _e( 'Client', 'orbis-timesheets' ); ?></th>
			<th><?php _e( 'Project', 'orbis-timesheets' ); ?></th>
			<th><?php _e( 'Description', 'orbis-timesheets' ); ?></th>
			<th><?php _e( 'Time', 'orbis-timesheets' ); ?></th>
			<th><?php _e( 'Total', 'orbis-timesheets' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $date = 0; foreach ( $result as $row ) : ?>
	
			<?php 
			if ( $date != $row->date ) :
				$date  = $row->date;
				$total = 0; 
				?>
			
				<tr>
					<td colspan="6">
						<h2><?php echo $row->date; ?></h2>
					</td>
				</tr>
			
			<?php endif; ?>
			
			<?php $total += $row->number_seconds; ?>
	
			<tr>
				<td>
					<?php echo $row->user_name; ?>
				</td>
				<td>
					<a href="<?php echo get_permalink( $row->client_post_id ); ?>" target="_blank">
						<?php echo $row->client_name; ?>
					</a>
				</td>
				<td>
					<a href="<?php echo get_permalink( $row->project_post_id ); ?>" target="_blank">
						<?php echo $row->project_name; ?>
					</a>
				</td>
				<td><?php echo $row->description; ?></td>
				<td>
					<?php 
					
					$title = sprintf(
						__( '%1$s billable, %2$s unbillable', 'orbis-timesheets' ),
						orbis_time( $row->billable_seconds ),
						orbis_time( $row->unbillable_seconds )
					);
					
					?>
					<a href="#" data-toggle="tooltip" title="<?php echo esc_attr( $title ); ?>">
						<?php echo orbis_time( $row->number_seconds ); ?>
					</a>
				</td>
				<td><?php echo orbis_time( $total ); ?></td>
			</tr>

		<?php endforeach; ?>
	</tbody>
</table>

<?php get_footer(); ?>
