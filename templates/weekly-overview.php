<?php 
/**
 * Template Name: Timesheet week
 */

get_header();

// Globals
global $wpdb;

// Functions
function orbis_format_timestamps( array $timestamps, $format ) {
	$dates = array();
	
	foreach( $timestamps as $key => $value ) {
		$dates[$key] = date( $format, $value );
	}
	
	return $dates;
}

$users = get_users( array(
	'meta_key'   => '_orbis_user',
	'meta_value' => 'true',
) );

// This week
$week_this = strtotime( 'previous Sunday' );

// Start date
$value = filter_input( INPUT_GET, 'date', FILTER_SANITIZE_STRING );
if ( empty( $value ) ) {
	$date = $week_this;
} else {
	$date = strtotime( $value );
}

$days = array(
	1 => strtotime( '+1 day', $date ),
	2 => strtotime( '+2 day', $date ),
	3 => strtotime( '+3 day', $date ),
	4 => strtotime( '+4 day', $date ),
	5 => strtotime( '+5 day', $date ),
	6 => strtotime( '+6 day', $date ),
	7 => strtotime( '+7 day', $date )
);

$query = "
	SELECT
		SUM(number_seconds)
	FROM
		$wpdb->orbis_timesheets
	WHERE
		user_id = %d
			AND
		`date` = %s
	GROUP BY
		user_id
	;
";

$previous = strtotime( '-1 week', $date );
$next     = strtotime( '+1 week', $date );

$url_previous  = add_query_arg( 'date', date( 'd-m-Y', $previous ) );
$url_next      = add_query_arg( 'date', date( 'd-m-Y', $next ) );
$url_week_this = add_query_arg( 'date', date( 'd-m-Y', $week_this ) );

?>

<form class="form-inline" method="get" action="">
	<div class="btn-group">
		<a class="btn btn-secondary" href="<?php echo $url_previous; ?>"><?php echo esc_html( _x( '<', 'previous', 'orbis_pronamic' ) ); ?></a>
		<a class="btn btn-secondary" href="<?php echo $url_next; ?>"><?php echo esc_html( _x( '>', 'next', 'orbis_pronamic' ) ); ?></a>
		<a class="btn btn-secondary" href="<?php echo $url_week_this; ?>"><?php echo esc_html( __( 'This week', 'orbis_pronamic' ) ); ?></a>
	</div>
</form>

<hr />

<div class="card">
	<div class="card-header">
		<?php

		printf(
			__( 'Week %s', 'orbis_pronamic' ),
			date_i18n( 'W', strtotime( '+1 day', $date ) )
		);

		?>
	</div>

	<div class="card-body">
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th><?php esc_html_e( 'User', 'orbis_pronamic' ); ?></th>

					<?php foreach ( $days as $day ): ?>

						<th><?php echo esc_html( date_i18n( 'D j M', $day ) ); ?></th>
					
					<?php endforeach; ?>

					<th><?php esc_html_e( 'Total', 'orbis_pronamic' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $users as $user ): ?>
				
					<tr>
						<td>
							<?php 

							echo esc_html( $user->display_name );

							$total = 0;

							?>
						</td>

						<?php foreach ( $days as $day ): ?>

							<?php 
							
							$q = $wpdb->prepare( $query, $user->ID, date( 'Y-m-d', $day ) );

							$seconds = $wpdb->get_var( $q );
							
							$total += $seconds;
							
							$url = add_query_arg( array(
								'start_date' => date( 'Y-m-d', $day ),
								'end_date'   => date( 'Y-m-d', $day ),
								'user'       => $user->ID,
							), 'http://in.pronamic.nl/rapporten/werk/' );
							
							?>
							<td>
								<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( orbis_time( $seconds ) ); ?></a>
							</td>
					
						<?php endforeach; ?>

						<td>
							<?php echo esc_html( orbis_time( $total ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php get_footer(); ?>
