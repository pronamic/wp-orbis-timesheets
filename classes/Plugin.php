<?php
/**
 * Plugin
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

/**
 * Plugin class
 */
class Plugin {
	/**
	 * Construct plugin.
	 */
	public function __construct() {
		include __DIR__ . '/../includes/functions.php';
		include __DIR__ . '/../includes/project-template.php';
		include __DIR__ . '/../includes/shortcodes.php';

		$controllers = [
			new EmailController( $this ),
			new RewriteController( $this ),
			new TemplateController( $this ),
		];

		if ( is_admin() ) {
			$controllers[] = new AdminController( $this );
		}

		foreach ( $controllers as $controller ) {
			if ( \method_exists( $controller, 'setup' ) ) {
				$controller->setup();
			}
		}

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize.
	 * 
	 * @return void
	 */
	public function init() {
		global $wpdb;

		$wpdb->orbis_timesheets = $wpdb->prefix . 'orbis_timesheets';
		$wpdb->orbis_activities = $wpdb->prefix . 'orbis_activities';

		$version = '1.2.4';

		if ( \get_option( 'orbis_timesheets_db_version' ) !== $version ) {
			$this->install();

			\update_option( 'orbis_timesheets_db_version', $version );
		}

		\register_taxonomy(
			'orbis_timesheets_activity',
			null,
			[
				'hierarchical'      => true,
				'labels'            => [
					'name'              => _x( 'Timesheets Activities', 'taxonomy general name', 'orbis-timesheets' ),
					'singular_name'     => _x( 'Timesheets Activity', 'taxonomy singular name', 'orbis-timesheets' ),
					'search_items'      => __( 'Search Timesheets Activities', 'orbis-timesheets' ),
					'all_items'         => __( 'All Timesheets Activities', 'orbis-timesheets' ),
					'parent_item'       => __( 'Parent Timesheets Activity', 'orbis-timesheets' ),
					'parent_item_colon' => __( 'Parent Activity:', 'orbis-timesheets' ),
					'edit_item'         => __( 'Edit Activity', 'orbis-timesheets' ),
					'update_item'       => __( 'Update Activity', 'orbis-timesheets' ),
					'add_new_item'      => __( 'Add New Activity', 'orbis-timesheets' ),
					'new_item_name'     => __( 'New Activity Name', 'orbis-timesheets' ),
					'menu_name'         => __( 'Activities', 'orbis-timesheets' ),
				],
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => 'genre' ],
			] 
		);
	}

	/**
	 * Install.
	 * 
	 * @return void
	 */
	public function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE $wpdb->orbis_activities (
				id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(128) NOT NULL,
				description TEXT NOT NULL,
				term_id BIGINT(20) UNSIGNED DEFAULT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;

			CREATE TABLE $wpdb->orbis_timesheets (
				id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
				created TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
				user_id BIGINT(20) UNSIGNED DEFAULT NULL,
				company_id BIGINT(16) UNSIGNED DEFAULT NULL,
				project_id BIGINT(16) UNSIGNED DEFAULT NULL,
				subscription_id BIGINT(16) UNSIGNED DEFAULT NULL,
				activity_id BIGINT(16) UNSIGNED DEFAULT NULL,
				description TEXT NOT NULL,
				`date` DATE NOT NULL DEFAULT '0000-00-00',
				number_seconds INT(16) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY company_id (company_id),
				KEY project_id (project_id),
				KEY subscription_id (subscription_id),
				KEY activity_id (activity_id)
			) $charset_collate;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta( $sql );

		\maybe_convert_table_to_utf8mb4( $wpdb->orbis_activities );
		\maybe_convert_table_to_utf8mb4( $wpdb->orbis_timesheets );
	}
}
