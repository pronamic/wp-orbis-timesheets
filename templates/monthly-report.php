<?php
/**
 * Monthly report
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date = new DateTimeImmutable();

if ( \array_key_exists( 'date', $_GET ) ) {
	$date_string = \sanitize_text_field( \wp_unslash( $_GET['date'] ) );

	try {
		$date_result = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date_string );

		if ( false === $date_result ) {
			throw new \Exception( 'Failed to parse date.' );
		}

		$date = $date_result->setTime( 0, 0 );
	} catch ( \Exception $e ) {
		\wp_die( $e->getMessage() );
	}
}

$start_date = $date->modify( 'first day of this month' );
$end_date   = $date->modify( 'last day of this month' );

$where = '1 = 1';

$where .= $wpdb->prepare( ' AND timesheet.date BETWEEN %s AND %s', $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) );
$where .= $wpdb->prepare( ' AND timesheet.user_id = %d', \get_current_user_id() );

$query_hours = "
	SELECT
		timesheet.id AS timesheet_id,
		project.id AS project_id,
		project.name AS project_name,
		project.post_id AS project_post_id,
		company.id AS company_id,
		company.name AS company_name,
		company.post_id AS company_post_id,
		user.display_name AS user_name,
		timesheet.date AS date,
		timesheet.description AS description,
		timesheet.number_seconds AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS timesheet
			LEFT JOIN
		$wpdb->orbis_companies AS company
				ON timesheet.company_id = company.id
			LEFT JOIN
		$wpdb->orbis_projects AS project
				ON timesheet.project_id = project.id
			LEFT JOIN
		$wpdb->users AS user
				ON timesheet.user_id = user.ID
	WHERE
		$where
	ORDER BY
		timesheet.date,
		timesheet.id
";

$data = $wpdb->get_results( $query_hours );

$weeks = [];

foreach ( $data as $item ) {
	$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', $item->date )->setTime( 0, 0 );

	$week_id = $date->format( 'o.W' );

	if ( ! \array_key_exists( $week_id, $weeks ) ) {
		$weeks[ $week_id ] = (object) [
			'start_date' => $date->modify( 'monday this week' ),
			'end_date'   => $date->modify( 'sunday this week' ),
			'data'       => [],
		];
	}

	$item->date = $date;

	$weeks[ $week_id ]->data[] = $item;
}

get_header();

?>
<div>
	<form class="mb-4 row gy-2 gx-3 align-items-center" method="get">
		<div class="col-auto">
			<label class="visually-hidden" for="date-input"><?php \esc_html_e( 'Date', 'orbis-timesheets' ); ?></label>
			<input name="date" type="date" class="form-control" id="date-input" value="<?php echo \esc_attr( $date->format( 'Y-m-d' ) ); ?>">
		</div>

		<div class="col-auto">
			<button type="submit" class="btn btn-primary"><?php \esc_html_e( 'Filter', 'orbis-timesheets' ); ?></button>
		</div>
	</form>

	<h2>
		<?php

		printf(
			/* translators: %s: Report name. */
			\__( 'Monthly report - %1$s - %2$s - %3$s', 'orbis-timesheets' ),
			\ucfirst( \wp_date( 'F Y', $start_date->getTimestamp() ) ),
			\esc_html( \wp_date( 'l j', $start_date->getTimestamp() ) ),
			\esc_html( \wp_date( 'l j F Y', $end_date->getTimestamp() ) )
		);

		?>
	</h2>

	<div class="card">
		<div class="card-header">
			<?php esc_html_e( 'Registrations', 'orbis-timesheets' ); ?>
		</div>

		<div class="card-body">
			<table class="table table-striped">
				<thead>
					<tr>
						<th scope="col"><?php \esc_html_e( 'Date', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Company', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Project', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Description', 'orbis-timesheets' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
					</tr>
				</thead>

				<tbody>

					<?php foreach ( $weeks as $week ) : ?>

						<tr class="fw-bold">
							<td colspan="5">
								<?php

								\printf(
									\__( 'Week %1$s: %2$s - %3$s', 'orbis-timesheets' ),
									\esc_html( \ltrim( $week->start_date->format( 'W' ), '0' ) ),
									\esc_html( \wp_date( 'l j F Y', $week->start_date->getTimestamp() ) ),
									\esc_html( \wp_date( 'l j F Y', $week->end_date->getTimestamp() ) )
								);

								?>
							</td>
						</tr>

						<?php foreach ( $week->data as $item ) : ?>

							<tr>
								<td class="text-nowrap">
									<?php echo \esc_html( \wp_date( 'l j F Y', $item->date->getTimestamp() ) ); ?>
								</td>
								<td>
									<?php echo \esc_html( $item->company_name ); ?>
								</td>
								<td>
									<?php echo \esc_html( $item->project_name ); ?>
								</td>
								<td>
									<?php echo \wp_kses_post( \apply_filters( 'orbis_timesheets_entry_description', $item->description ) ); ?>
								</td>
								<td>
									<?php echo \esc_html( \orbis_time( $item->number_seconds ) ); ?>
								</td>
							</tr>

						<?php endforeach; ?>

						<tr class="fw-bold">
							<th colspan="4" scope="row">
								<?php

								\printf(
									\__( 'Subtotal week %s', 'orbis-timesheets' ),
									\esc_html( \ltrim( $week->start_date->format( 'W' ), '0' ) )
								);

								?>
							</th>
							<td>
								<?php

								$total = \array_sum( \wp_list_pluck( $week->data, 'number_seconds' ) );

								echo \esc_html( \orbis_time( $total ) );

								?>
							</td>
						</tr>
						<tr>
							<td colspan="5">
								<hr>
							</td>
						</tr>

					<?php endforeach; ?>

				</tbody>

				<tfoot>
					<tr class="fw-bold">
						<th colspan="4" scope="row">
							<?php \esc_html_e( 'Total', 'orbis-timesheets' ); ?>
						</th>
						<td>
							<?php

							$total = \array_sum( \wp_list_pluck( $data, 'number_seconds' ) );

							echo \esc_html( \orbis_time( $total ) );

							?>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
<?php

get_footer();