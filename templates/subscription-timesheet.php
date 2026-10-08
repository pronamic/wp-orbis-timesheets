<?php
/**
 * Subscription timesheet
 *
 * Shows the registered time per month in the current subscription period,
 * which starts on the anniversary of the activation date, and all time
 * registrations of the subscription.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$subscription = $wpdb->get_row(
	$wpdb->prepare(
		"
		SELECT
			subscription.*,
			product.time_per_year
		FROM
			$wpdb->orbis_subscriptions AS subscription
				LEFT JOIN
			$wpdb->orbis_products AS product
					ON subscription.product_id = product.id
		WHERE
			subscription.post_id = %d
		LIMIT
			1
		;
		",
		get_the_ID()
	)
);

if ( null === $subscription ) {
	return;
}

$now = new DateTimeImmutable( 'now', wp_timezone() );

$activation_date = new DateTimeImmutable( $subscription->activation_date, wp_timezone() );

$number_years = $activation_date->diff( $now )->y;

$period_start = $activation_date->modify( '+' . $number_years . ' year' );
$period_end   = $period_start->modify( '+1 year -1 day' );

$results = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
			YEAR( timesheet.date ) AS year,
			MONTH( timesheet.date ) AS month,
			SUM( timesheet.number_seconds ) AS number_seconds
		FROM
			$wpdb->orbis_timesheets AS timesheet
		WHERE
			timesheet.subscription_id = %d
				AND
			timesheet.date BETWEEN %s AND %s
		GROUP BY
			YEAR( timesheet.date ), MONTH( timesheet.date )
		;
		",
		$subscription->id,
		$period_start->format( 'Y-m-d' ),
		$period_end->format( 'Y-m-d' )
	)
);

$seconds_per_month = [];

foreach ( $results as $result ) {
	$seconds_per_month[ $result->year . '-' . $result->month ] = (int) $result->number_seconds;
}

$months = [];

$month = $period_start->modify( 'first day of this month' );

while ( $month <= $now && $month <= $period_end ) {
	$key = $month->format( 'Y-n' );

	$months[] = (object) [
		'date'           => $month,
		'number_seconds' => $seconds_per_month[ $key ] ?? 0,
	];

	$month = $month->modify( '+1 month' );
}

$timesheet_period = (object) [
	'subscription'      => $subscription,
	'start'             => $period_start,
	'end'               => $period_end,
	'months'            => $months,
	'total_seconds'     => array_sum( $seconds_per_month ),
	'available_seconds' => ( null === $subscription->time_per_year ) ? null : (int) $subscription->time_per_year,
];

?>
<div class="card mb-3">
	<div class="card-header">
		<?php

		printf(
			/* translators: 1: period start date, 2: period end date. */
			esc_html__( 'Timesheet period %1$s - %2$s', 'orbis-timesheets' ),
			esc_html( wp_date( get_option( 'date_format' ), $period_start->getTimestamp() ) ),
			esc_html( wp_date( get_option( 'date_format' ), $period_end->getTimestamp() ) )
		);

		?>
	</div>

	<div class="table-responsive" id="orbis-subscription-timesheet-months">
		<table class="table table-striped mb-0">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Month', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th scope="row"><?php esc_html_e( 'Total', 'orbis-timesheets' ); ?></th>
					<td>
						<?php

						echo esc_html( orbis_time( $timesheet_period->total_seconds ) );

						if ( null !== $timesheet_period->available_seconds ) {
							echo esc_html( ' / ' . orbis_time( $timesheet_period->available_seconds ) );
						}

						?>
					</td>
				</tr>
			</tfoot>

			<tbody>

				<?php foreach ( $months as $month ) : ?>

					<tr>
						<th scope="row">
							<?php echo esc_html( ucfirst( wp_date( 'F Y', $month->date->getTimestamp() ) ) ); ?>
						</th>
						<td>
							<?php echo esc_html( orbis_time( $month->number_seconds ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>

	<div class="card-footer">
		<button type="button" class="btn btn-secondary btn-sm float-end" onclick="navigator.clipboard.writeText( document.getElementById( 'orbis-subscription-timesheet-months' ).innerHTML );">
			<i class="fas fa-paste"></i> <?php esc_html_e( 'Copy HTML table', 'orbis-timesheets' ); ?>
		</button>
	</div>
</div>
<?php

/**
 * Subscription timesheet period.
 *
 * Allows themes and plugins to add content, such as support messages,
 * based on the registered time in the current subscription period.
 *
 * @param object $timesheet_period Object with `subscription`, `start`, `end`, `months`, `total_seconds` and `available_seconds`.
 */
do_action( 'orbis_subscription_timesheet_period', $timesheet_period );

// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- The display names of the registering users are needed.
$registrations = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
			timesheet.date,
			timesheet.description,
			timesheet.number_seconds,
			user.display_name AS user_display_name
		FROM
			$wpdb->orbis_timesheets AS timesheet
				LEFT JOIN
			$wpdb->users AS user
					ON timesheet.user_id = user.ID
		WHERE
			timesheet.subscription_id = %d
		ORDER BY
			timesheet.date ASC, timesheet.id ASC
		;
		",
		$subscription->id
	)
);
// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

$note = get_option( 'orbis_timesheets_note' );

?>
<div class="card mb-3">
	<div class="card-header"><?php esc_html_e( 'Timesheet', 'orbis-timesheets' ); ?></div>

	<?php if ( empty( $registrations ) ) : ?>

		<div class="card-body">
			<p class="text-muted m-0">
				<?php esc_html_e( 'There are no time registrations for this subscription.', 'orbis-timesheets' ); ?>
			</p>
		</div>

	<?php else : ?>

		<?php if ( $note ) : ?>

			<div class="card-body">
				<div class="alert alert-warning mb-0" role="alert">
					<i class="fas fa-exclamation-triangle"></i> <?php echo wp_kses_post( $note ); ?>
				</div>
			</div>

		<?php endif; ?>

		<div class="table-responsive">
			<table class="table table-striped mb-0">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php esc_html_e( 'User', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Description', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th scope="row" colspan="3"><?php esc_html_e( 'Total', 'orbis-timesheets' ); ?></th>
						<td>
							<strong><?php echo esc_html( orbis_time( array_sum( wp_list_pluck( $registrations, 'number_seconds' ) ) ) ); ?></strong>
						</td>
					</tr>
				</tfoot>

				<tbody>

					<?php foreach ( $registrations as $registration ) : ?>

						<tr>
							<td>
								<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $registration->date ) ) ); ?>
							</td>
							<td>
								<?php echo esc_html( $registration->user_display_name ); ?>
							</td>
							<td>
								<?php echo esc_html( $registration->description ); ?>
							</td>
							<td>
								<?php echo esc_html( orbis_time( $registration->number_seconds ) ); ?>
							</td>
						</tr>

					<?php endforeach; ?>

				</tbody>
			</table>
		</div>

	<?php endif; ?>
</div>
