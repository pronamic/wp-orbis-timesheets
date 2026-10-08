<?php
/**
 * Project activities chart
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

$results = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
			SUM( timesheet.number_seconds ) AS total_seconds,
			activity.name AS activity_name
		FROM
			$wpdb->orbis_timesheets AS timesheet
				LEFT JOIN
			$wpdb->orbis_activities AS activity
					ON timesheet.activity_id = activity.id
				LEFT JOIN
			$wpdb->orbis_projects AS project
					ON timesheet.project_id = project.id
		WHERE
			project.post_id = %d
		GROUP BY
			activity.id
		;
		",
		get_the_ID()
	)
);

$flot_data = [];

foreach ( $results as $row ) {
	$flot_data[] = [
		'label' => sprintf(
			'<strong>%s</strong> - %s',
			esc_html( orbis_time( $row->total_seconds ) ),
			esc_html( $row->activity_name )
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
	<div id="project-activities-chart" class="graph" style="height: 400px; width: 100%;"></div>

	<?php orbis_flot( 'project-activities-chart', $flot_data, $flot_options ); ?>
</div>
