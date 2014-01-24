<?php

class Orbis_Timesheets_Admin {
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		add_menu_page(
			__( 'Orbis Timesheets', 'orbis_timesheets' ),
			__( 'Timesheets', 'orbis_timesheets' ),
			'manage_options',
			'orbis_timesheets',
			array( $this, 'page_admin' ),
			'dashicons-performance',
			40
		);

		add_submenu_page(
			'orbis_timesheets', // parent_slug
			__( 'Orbis Timesheets Settings', 'orbis' ), // page_title
			__( 'Settings', 'orbis' ), // menu_title
			'manage_options', // capability
			'orbis_timesheets_settings', // menu_slug
			array( $this, 'page_settings' ) // function
		);
	}

	public function page_admin() {
		$this->plugin->plugin_include( 'admin/page-timesheets.php' );
	}

	public function page_settings() {
		$this->plugin->plugin_include( 'admin/page-settings.php' );
	}
}
