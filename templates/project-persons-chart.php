<?php
/**
 * Project persons chart
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

// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- The display names of the registering users are needed.
$results = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
			SUM( timesheet.number_seconds ) AS total_seconds,
			user.display_name
		FROM
			$wpdb->orbis_timesheets AS timesheet
				LEFT JOIN
			$wpdb->users AS user
					ON timesheet.user_id = user.ID
				LEFT JOIN
			$wpdb->orbis_projects AS project
					ON timesheet.project_id = project.id
		WHERE
			project.post_id = %d
		GROUP BY
			user.ID
		;
		",
		get_the_ID()
	)
);
// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

$flot_data = [];

foreach ( $results as $row ) {
	$flot_data[] = [
		'label' => sprintf(
			'<strong>%s</strong> - %s',
			esc_html( orbis_time( $row->total_seconds ) ),
			esc_html( $row->display_name )
		),
		'data'  => [
			[ 0, $row->total_seconds ],
		],
	];
}

$flot_options = [
	'series' => [
		'pie' => [
			'innerRadius' => 0.5,
			'show'        => true,
		],
	],
];

?>
<div class="card-body">
	<div id="project-persons-chart" class="graph" style="height: 400px; width: 100%;"></div>

	<?php orbis_flot( 'project-persons-chart', $flot_data, $flot_options ); ?>
</div>
