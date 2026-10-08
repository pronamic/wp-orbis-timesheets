<?php
/**
 * Project months
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

$available_seconds = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT number_seconds FROM $wpdb->orbis_projects WHERE post_id = %d;",
		get_the_ID()
	)
);

$months = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
			YEAR( timesheet.date ) AS year,
			MONTH( timesheet.date ) AS month,
			SUM( timesheet.number_seconds ) AS number_seconds
		FROM
			$wpdb->orbis_timesheets AS timesheet
				INNER JOIN
			$wpdb->orbis_projects AS project
					ON timesheet.project_id = project.id
		WHERE
			project.post_id = %d
		GROUP BY
			YEAR( timesheet.date ), MONTH( timesheet.date )
		ORDER BY
			YEAR( timesheet.date ) ASC, MONTH( timesheet.date ) ASC
		;
		",
		get_the_ID()
	)
);

?>
<div class="table-responsive" id="orbis-project-timesheet-months">
	<table class="table table-striped mb-0">
		<thead>
			<tr>
				<th class="border-top-0" scope="col"><?php esc_html_e( 'Month', 'orbis-timesheets' ); ?></th>
				<th class="border-top-0" scope="col"><?php esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<th scope="row"><?php esc_html_e( 'Total', 'orbis-timesheets' ); ?></th>
				<td>
					<?php

					$total_seconds = array_sum( wp_list_pluck( $months, 'number_seconds' ) );

					echo esc_html( orbis_time( $total_seconds ) );

					if ( null !== $available_seconds ) {
						echo esc_html( ' / ' . orbis_time( $available_seconds ) );
					}

					?>
				</td>
			</tr>
		</tfoot>

		<tbody>

			<?php foreach ( $months as $month ) : ?>

				<tr>
					<th scope="row">
						<?php

						$timestamp = mktime( 0, 0, 0, (int) $month->month, 1, (int) $month->year );

						echo esc_html( ucfirst( date_i18n( 'F Y', $timestamp ) ) );

						?>
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
	<button type="button" class="btn btn-secondary btn-sm float-end" onclick="navigator.clipboard.writeText( document.getElementById( 'orbis-project-timesheet-months' ).innerHTML );">
		<i class="fas fa-paste"></i> <?php esc_html_e( 'Copy HTML table', 'orbis-timesheets' ); ?>
	</button>
</div>
