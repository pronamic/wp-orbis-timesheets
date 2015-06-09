<?php

class Orbis_Timesheets_Admin {
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Taxonomy actions
		// @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-includes/taxonomy.php#L2529
		add_action( 'created_orbis_timesheets_activity', array( $this, 'sync_activity' ), 10 );
		// @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-includes/taxonomy.php#L3024
		add_action( 'edited_orbis_timesheets_activity', array( $this, 'sync_activity' ), 10 );

		add_filter( 'parent_file', array( $this, 'parent_file' ) );
	}

	/**
	 * Sync activity
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id Term taxonomy ID.
	 */
	public function sync_activity( $term_id ) {
		global $wpdb;

		$term = get_term( $term_id, 'orbis_timesheets_activity' );

		if ( is_wp_error( $term ) || is_null( $term ) ) {

		} else {
			$activity_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->orbis_activities WHERE term_id = %d;", $term_id ) );

			// Format and data
			$format = array(
				'name'        => '%s',
				'description' => '%s',
				'term_id'     => '%d',
			);

			$data = array(
				'name'        => $term->name,
				'description' => $term->description,
				'term_id'     => $term_id,
			);

			// Activity
			if ( $activity_id ) {
				$result = $wpdb->update( $wpdb->orbis_activities, $data, array( 'id' => $activity_id ), $format );
			} else {
				$result = $wpdb->insert( $wpdb->orbis_activities, $data, $format );
			}
		}
	}

	public function admin_init() {
		// General
		add_settings_section(
			'orbis_timesheets_settings_general', // id
			__( 'General Settings', 'orbis_timesheets' ), // title
			'__return_false', // callback
			'orbis_timesheets_settings' // page
		);

		add_settings_field(
			'orbis_timesheets_registration_limit_lower', // id
			__( 'Registration Limit Lower', 'orbis_timesheets' ), // title
			array( $this, 'input_select' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_general', // section
			array(
				'label_for' => 'orbis_timesheets_registration_limit_lower',
				'options'   => array(
					'0'       => __( 'None', 'orbis_timesheets' ),
					'1 day'   => __( '1 Day', 'orbis_timesheets' ),
					'3 days'  => __( '3 Days', 'orbis_timesheets' ),
					'1 week'  => __( '1 Week', 'orbis_timesheets' ),
					'1 month' => __( '1 Month', 'orbis_timesheets' ),
				),
			) // args
		);

		register_setting( 'orbis_timesheets', 'orbis_timesheets_registration_limit_lower' );
	}

	/**
	 * Input text
	 *
	 * @param array $args
	 */
	public function input_text( $args ) {
		$name = $args['label_for'];

		$classes = array( 'regular-text' );
		if ( isset( $args['classes'] ) ) {
			$classes = $args['classes'];
		}

		printf(
			'<input name="%s" id="%s" type="text" class="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( get_option( $name, '' ) )
		);
	}

	/**
	 * Input checkbox
	 *
	 * @param array $args
	 */
	public function input_checkbox( $args ) {
		$name = $args['label_for'];

		$classes = array();
		if ( isset( $args['classes'] ) ) {
			$classes = $args['classes'];
		}

		printf(
			'<input name="%s" id="%s" type="checkbox" class="%s" %s />',
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( implode( ' ', $classes ) ),
			checked( 'on', get_option( $name ), false )
		);
	}

	/**
	 * Input select
	 *
	 * @param array $args
	 */
	public function input_select( $args ) {
		$name = $args['label_for'];

		$classes = array();
		if ( isset( $args['classes'] ) ) {
			$classes = $args['classes'];
		}

		$options = array();
		if ( isset( $args['options'] ) ) {
			$options = $args['options'];
		}

		$multiple = false;
		if ( isset( $args['multiple'] ) && $args['multiple'] ) {
			$multiple = true;
		}

		printf(
			'<select name="%s" id="%s" class="%s" %s>',
			esc_attr( $name ) . ( $multiple ? '[]' : '' ),
			esc_attr( $name ),
			esc_attr( implode( ' ', $classes ) ),
			$multiple ? 'multiple="multiple" size="10"' : ''
		);

		$current_value = get_option( $name, '' );

		foreach ( $options as $option_key => $option ) {

			$selected = ( is_string( $current_value ) && $option_key == $current_value ) ||
						( is_array( $current_value ) && in_array( $option_key, $current_value ) );

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $option_key ),
				selected( $selected, true, false ),
				esc_attr( $option )
			);
		}

		echo '</select>';
	}

	/**
	 * Parent file
	 *
	 * @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-admin/menu-header.php#L23
	 * @param string $parent_file
	 * @return string
	 */
	public function parent_file( $parent_file ) {
		$screen = get_current_screen();

		if ( 'orbis_timesheets_activity' == $screen->taxonomy ) {
			// Make sure the Orbis Timesheets menu is active
			$parent_file = 'orbis_timesheets';
		}

		return $parent_file;
	}

	public function admin_menu() {
		add_menu_page(
			__( 'Orbis Timesheets', 'orbis_timesheets' ),
			__( 'Timesheets', 'orbis_timesheets' ),
			'manage_options',
			'orbis_timesheets',
			array( $this, 'page_admin' ),
			'dashicons-clock',
			40
		);

		add_submenu_page(
			'orbis_timesheets', // parent_slug
			__( 'Orbis Timesheets Settings', 'orbis_timesheets' ), // page_title
			__( 'Settings', 'orbis_timesheets' ), // menu_title
			'manage_options', // capability
			'orbis_timesheets_settings', // menu_slug
			array( $this, 'page_settings' ) // function
		);

		global $submenu;

		/*
		 * Add taxonomy 'orbis_timesheets_activity' to sub menu
		 * @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-admin/menu.php#L59
		 */
		if ( isset( $submenu['orbis_timesheets'] ) ) {
			$tax = get_taxonomy( 'orbis_timesheets_activity' );

			$submenu['orbis_timesheets'][] = array(
				esc_attr( $tax->labels->menu_name ),
				$tax->cap->manage_terms,
				add_query_arg( 'taxonomy', $tax->name, 'edit-tags.php' ),
			);
		}
	}

	public function page_admin() {
		$this->plugin->plugin_include( 'admin/page-timesheets.php' );
	}

	public function page_settings() {
		$this->plugin->plugin_include( 'admin/page-settings.php' );
	}
}
