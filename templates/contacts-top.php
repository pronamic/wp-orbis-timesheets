<?php
/**
 * Contacts top
 *
 * Lists the contacts with the most registered time in a period.
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

$contacts_table = \Pronamic\Orbis\Contacts\ContactsTable::get_table_name();

$query = "
	SELECT
		contact.name AS contact_name,
		contact.post_id AS contact_post_id,
		SUM( timesheet.number_seconds ) AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS timesheet
			INNER JOIN
		$contacts_table AS contact
				ON timesheet.contact_id = contact.id
	WHERE
		$condition
	GROUP BY
		contact.id
	ORDER BY
		number_seconds DESC
	LIMIT
		100
	;
";

$contacts = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

get_header();

?>
<form class="d-flex justify-content-end gap-2 mb-3" method="get" action="">
	<label class="visually-hidden" for="orbis-timesheets-contacts-top-period"><?php esc_html_e( 'Period', 'orbis-timesheets' ); ?></label>

	<select name="start" id="orbis-timesheets-contacts-top-period" class="form-select w-auto">
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
					<th scope="col"><?php esc_html_e( 'Contact', 'orbis-timesheets' ); ?></th>
					<th scope="col" class="text-end"><?php esc_html_e( 'Time', 'orbis-timesheets' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $contacts as $contact ) : ?>

					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $contact->contact_post_id ) ); ?>"><?php echo esc_html( $contact->contact_name ); ?></a>
						</td>
						<td class="text-end">
							<?php echo esc_html( orbis_time( $contact->number_seconds ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>
</div>
<?php

get_footer();
