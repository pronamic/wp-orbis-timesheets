<?php
/**
 * Monthly report
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Tasks
 */

$start_date = new DateTime( 'first day of this month' );
$end_date   = new DateTime( 'last day of this month' );

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

get_header();

?>
<div>
	<h2>
		<?php

		printf(
			/* translators: %s: Report name. */
			\__( 'Monthly report - %s', 'orbis-timesheets' ),
			\ucfirst( \wp_date( 'F Y', $start_date->getTimestamp() ) )
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

					<?php $week_id = ''; ?>

					<?php foreach ( $data as $item ) : ?>

						<?php

						$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', $item->date )->setTime( 0, 0 );

						$item_week_id = $date->format( 'o.W' );

						if ( $week_id !== $item_week_id ) {
							$week_id = $item_week_id;

							$week_start = $date->modify( 'monday this week' );
							$week_end   = $date->modify( 'sunday this week' );

							?>
							<tr class="text-opacity-50">
								<td colspan="5">
									<?php

									\printf(
										\__( 'Week %s: %s - %s', 'orbis-timesheets' ),
										\ltrim( $date->format( 'W' ), '0' ),
										\wp_date( 'l j F Y', $week_start->getTimestamp() ),
										\wp_date( 'l j F Y', $week_end->getTimestamp() )
									);

									?>
								</td>
							</tr>
							<?php
						}

						?>
						<tr>
							<td class="text-nowrap">
								<?php echo \esc_html( \wp_date( 'l j F Y', $date->getTimestamp() ) ); ?>
							</td>
							<td>
								<?php echo \esc_html( $item->company_name ); ?>
							</td>
							<td>
								<?php echo \esc_html( $item->project_name ); ?>
							</td>
							<td>
								<?php echo \esc_html( $item->description ); ?>
							</td>
							<td>
								<?php echo \esc_html( orbis_time( $item->number_seconds ) ); ?>
							</td>
						</tr>

					<?php endforeach; ?>

				</tbody>

				<tfoot>
					<tr>
						<th colspan="4" scope="row">
							<?php \esc_html_e( 'Total', 'orbis-timesheets' ); ?>
						</th>
						<td class="fw-bold">
							<?php

							$total = \array_sum( \wp_list_pluck( $data, 'number_seconds' ) );

							echo \esc_html( orbis_time( $total ) );

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