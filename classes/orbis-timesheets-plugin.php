<?php

class Orbis_Timesheets_Plugin extends Orbis_Plugin {
	public function __construct( $file ) {
		parent::__construct( $file );

		$this->set_name( 'orbis_timesheets' );
		$this->set_db_version( '1.0' );

		$this->plugin_include( 'includes/post.php' );
		$this->plugin_include( 'includes/template.php' );
		$this->plugin_include( 'includes/project-template.php' );
	}

	public function loaded() {
		$this->load_textdomain( 'orbis_timesheets', '/languages/' );
	}
}
