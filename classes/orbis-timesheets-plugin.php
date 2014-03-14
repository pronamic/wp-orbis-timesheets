<?php

class Orbis_Timesheets_Plugin extends Orbis_Plugin {
	public function __construct( $file ) {
		parent::__construct( $file );

		$this->set_name( 'orbis_timesheets' );
		$this->set_db_version( '1.2.1' );

		$this->plugin_include( 'includes/functions.php' );
		$this->plugin_include( 'includes/post.php' );
		$this->plugin_include( 'includes/template.php' );
		$this->plugin_include( 'includes/project-template.php' );
		$this->plugin_include( 'includes/shortcodes.php' );

		orbis_register_table( 'orbis_timesheets', 'orbis_hours_registration' );
		orbis_register_table( 'orbis_activities' );

		$this->email = new Orbis_Timesheets_Email( $this );

		if ( is_admin() ) {
			$this->admin = new Orbis_Timesheets_Admin( $this );
		}
	}

	public function loaded() {
		$this->load_textdomain( 'orbis_timesheets', '/languages/' );
	}

	public function install() {
		// Tables
		orbis_install_table( 'orbis_activities', "
			id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(128) NOT NULL,
			description TEXT NOT NULL,
			PRIMARY KEY  (id)
		" );

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

		parent::install();
	}
}
