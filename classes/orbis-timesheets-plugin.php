<?php

class Orbis_Timesheets_Plugin extends Orbis_Plugin {
	public function __construct( $file ) {
		parent::__construct( $file );

		$this->set_name( 'orbis_timesheets' );
		$this->set_db_version( '1.2.4' );

		$this->plugin_include( 'includes/functions.php' );
		$this->plugin_include( 'includes/template.php' );
		$this->plugin_include( 'includes/project-template.php' );
		$this->plugin_include( 'includes/shortcodes.php' );

		orbis_register_table( 'orbis_timesheets', 'orbis_hours_registration' );
		orbis_register_table( 'orbis_activities' );

		$this->email = new Orbis_Timesheets_Email( $this );

		if ( is_admin() ) {
			$this->admin = new Orbis_Timesheets_Admin( $this );
		}

		// Actions
		add_action( 'init', array( $this, 'init' ) );
	}

	public function loaded() {
		$this->load_textdomain( 'orbis_timesheets', '/languages/' );
	}

	public function install() {
		// Tables
		orbis_install_table( 'orbis_activities', '
			id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(128) NOT NULL,
			description TEXT NOT NULL,
			term_id BIGINT(20) UNSIGNED DEFAULT NULL,
			PRIMARY KEY  (id)
		' );

		orbis_install_table( 'orbis_timesheets', "
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
		" );

		// Maybe convert
		global $wpdb;

		maybe_convert_table_to_utf8mb4( $wpdb->orbis_activities );
		maybe_convert_table_to_utf8mb4( $wpdb->orbis_timesheets );

		parent::install();
	}

	public function init() {
		register_taxonomy( 'orbis_timesheets_activity', null, array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'              => _x( 'Timesheets Activities', 'taxonomy general name', 'orbis_timesheets' ),
				'singular_name'     => _x( 'Timesheets Activity', 'taxonomy singular name', 'orbis_timesheets' ),
				'search_items'      => __( 'Search Timesheets Activities', 'orbis_timesheets' ),
				'all_items'         => __( 'All Timesheets Activities', 'orbis_timesheets' ),
				'parent_item'       => __( 'Parent Timesheets Activity', 'orbis_timesheets' ),
				'parent_item_colon' => __( 'Parent Activity:', 'orbis_timesheets' ),
				'edit_item'         => __( 'Edit Activity', 'orbis_timesheets' ),
				'update_item'       => __( 'Update Activity', 'orbis_timesheets' ),
				'add_new_item'      => __( 'Add New Activity', 'orbis_timesheets' ),
				'new_item_name'     => __( 'New Activity Name', 'orbis_timesheets' ),
				'menu_name'         => __( 'Activities', 'orbis_timesheets' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'genre' ),
		) );
	}
}
