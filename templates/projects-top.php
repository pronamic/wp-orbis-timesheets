<?php
/**
 * Projects top
 *
 * Lists the projects with the most registered time in a period.
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

$period_options = [
	'-1 week'   => __( 'Last week', 'orbis-timesheets' ),
	'-1 month'  => __( 'Last month', 'orbis-timesheets' ),
	'-3 months' => __( 'Last 3 months', 'orbis-timesheets' ),
	'-6 months' => __( 'Last 6 months', 'orbis-timesheets' ),
	'-1 year'   => __( 'Last year', 'orbis-timesheets' ),
	'all'       => __( 'All time', 'orbis-timesheets' ),
];

$period = orbis_timesheets_filter_text_input( INPUT_GET, 'start' );

if ( ! array_key_exists( (string) $period, $period_options ) ) {
	$period = '-1 week';
}

$condition = '1 = 1';

if ( 'all' !== $period ) {
	$condition = $wpdb->prepare(
		'timesheet.date >= %s',
		gmdate( 'Y-m-d', strtotime( $period ) )
	);
}

$customer_select = 'NULL AS customer_name, NULL AS customer_post_id';
$customer_join   = '';

if ( class_exists( \Pronamic\Orbis\Contacts\ContactsTable::class ) ) {
	$contacts_table = \Pronamic\Orbis\Contacts\ContactsTable::get_table_name();

	$customer_select = 'customer.name AS customer_name, customer.post_id AS customer_post_id';
	$customer_join   = "LEFT JOIN $contacts_table AS customer ON project.customer_id = customer.id";
}

$query = "
	SELECT
		project.name AS project_name,
		project.post_id AS project_post_id,
		$customer_select,
		SUM( timesheet.number_seconds ) AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS timesheet
			INNER JOIN
		$wpdb->orbis_projects AS project
				ON timesheet.project_id = project.id
		$customer_join
	WHERE
		$condition
	GROUP BY
		project.id
	ORDER BY
		number_seconds DESC
	LIMIT
		100
	;
";

$projects = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

get_header();

?>
<form class="d-flex justify-content-end gap-2 mb-3" method="get" action="">
	<label class="visually-hidden" for="orbis-timesheets-projects-top-period"><?php esc_html_e( 'Period', 'orbis-timesheets' ); ?></label>

	<select name="start" id="orbis-timesheets-projects-top-period" class="form-select w-auto">
		<?php

		foreach ( $period_options as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $period, $value, false ),
				esc_html( $label )
			);
		}

		?>
	</select>

	<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Filter', 'orbis-timesheets' ); ?></button>
</form>

<div class="card">
	<div class="table-responsive">
		<table class="table table-striped table-hover mb-0">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Customer', 'orbis-timesheets' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Project', 'orbis-timesheets' ); ?></th>
					<th scope="col" class="text-end"><?php esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $projects as $project ) : ?>

					<tr>
						<td>
							<?php if ( null !== $project->customer_post_id ) : ?>

								<a href="<?php echo esc_url( get_permalink( $project->customer_post_id ) ); ?>"><?php echo esc_html( $project->customer_name ); ?></a>

							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( get_permalink( $project->project_post_id ) ); ?>"><?php echo esc_html( $project->project_name ); ?></a>
						</td>
						<td class="text-end">
							<?php echo esc_html( orbis_time( $project->number_seconds ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>
</div>
<?php

get_footer();
