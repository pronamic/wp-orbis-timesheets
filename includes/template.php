<?php

function orbis_timesheets_render_project_timesheet() {
	global $orbis_timesheets_plugin;

	$orbis_timesheets_plugin->plugin_include( 'templates/project-timesheet.php' );
}

/**
 * Project section
 *
 * @param array $sections
 */
function orbis_timesheets_project_section( $sections ) {
	array_unshift( $sections, array(
		'id'       => 'timesheet',
		'slug'     => __( 'timesheet', 'orbis' ),
		'name'     => __( 'Timesheet', 'orbis' ),
		'callback' => 'orbis_timesheets_render_project_timesheet',
	) );

	return $sections;
}

add_filter( 'orbis_project_sections', 'orbis_timesheets_project_section' );
