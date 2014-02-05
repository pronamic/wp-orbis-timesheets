<?php
/*
Plugin Name: Orbis Timesheets
Plugin URI: http://www.orbiswp.com/
Description: Time Management, Timesheet, Time Tracking solution for WordPress. Orbis Timesheets enables you to track your work time.

Version: 1.2.0
Requires at least: 3.5

Author: Pronamic
Author URI: http://www.pronamic.eu/

Text Domain: orbis_timesheets
Domain Path: /languages/

License: Copyright (c) Pronamic

GitHub URI: https://github.com/pronamic/wp-orbis-subscriptions
*/

function orbis_timesheets_bootstrap() {
	// Classes
	require_once 'classes/orbis-timesheets-plugin.php';
	require_once 'classes/orbis-timesheets-admin.php';
	require_once 'classes/orbis-timesheet-entry.php';
	require_once 'classes/orbis-timesheets-email.php';

	// Initialize
	global $orbis_timesheets_plugin;

	$orbis_timesheets_plugin = new Orbis_Timesheets_Plugin( __FILE__ );
}

add_action( 'orbis_bootstrap', 'orbis_timesheets_bootstrap' );
