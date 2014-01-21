<?php

function orbis_timesheets_shortcode( $atts ) {
	global $orbis_timesheets_plugin;

	$return  = '';

	ob_start();

	$orbis_timesheets_plugin->plugin_include( 'templates/timesheets.php' );

	$return = ob_get_contents();

	ob_end_clean();

	return $return;
}

add_shortcode( 'orbis_timesheets', 'orbis_timesheets_shortcode' );
