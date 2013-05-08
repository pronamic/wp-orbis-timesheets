<?php

function orbis_timesheets_render_project_timesheet() {
	if ( is_singular( 'orbis_project' ) ) {
		global $orbis_timesheets_plugin;
	
		$orbis_timesheets_plugin->plugin_include( 'templates/project-timesheet.php' );
	}
}

add_action( 'orbis_after_main_content', 'orbis_timesheets_render_project_timesheet' );

