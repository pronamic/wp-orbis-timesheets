<?php
/*
Plugin Name: Orbis Timesheets
Plugin URI: http://orbiswp.com/
Description: 

Version: 0.1
Requires at least: 3.5

Author: Pronamic
Author URI: http://pronamic.eu/

Text Domain: orbis_timesheets
Domain Path: /languages/

License: GPL

GitHub URI: https://github.com/pronamic/wp-orbis-subscriptions
*/

function orbis_timesheets_bootstrap() {
	// Classes
	require_once 'classes/orbis-timesheets-plugin.php';
	
	// Functions
	require_once 'includes/functions.php';

	// Initialize
	global $orbis_timesheets_plugin;
	
	$orbis_timesheets_plugin = new Orbis_Timesheets_Plugin( __FILE__ );
}

add_action( 'orbis_bootstrap', 'orbis_timesheets_bootstrap' );
