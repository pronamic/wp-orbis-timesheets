<?php
/**
 * Orbis Timesheets
 *
 * @package   Pronamic\Orbis\Timesheets
 * @author    Pronamic
 * @copyright 2024 Pronamic
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Orbis Timesheets
 * Plugin URI:        https://wp.pronamic.directory/plugins/orbis-timesheets/
 * Description:       Time Management, Timesheet, Time Tracking solution for WordPress. Orbis Timesheets enables you to track your work time.
 * Version:           1.3.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Pronamic
 * Author URI:        https://www.pronamic.eu/
 * Text Domain:       orbis-timesheets
 * Domain Path:       /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://wp.pronamic.directory/plugins/orbis-timesheets/
 * GitHub URI:        https://github.com/pronamic/wp-orbis-timesheets
 */

namespace Pronamic\Orbis\Timesheets;

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

/**
 * Bootstrap.
 */
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'orbis-timesheets', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 

		global $orbis_timesheets_plugin;

		$orbis_timesheets_plugin = new Plugin();
	}
);
