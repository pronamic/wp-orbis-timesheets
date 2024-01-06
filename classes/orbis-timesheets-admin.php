<?php

class Orbis_Timesheets_Admin {
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Taxonomy actions
		// @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-includes/taxonomy.php#L2529
		add_action( 'created_orbis_timesheets_activity', [ $this, 'sync_activity' ], 10 );
		// @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-includes/taxonomy.php#L3024
		add_action( 'edited_orbis_timesheets_activity', [ $this, 'sync_activity' ], 10 );

		add_filter( 'parent_file', [ $this, 'parent_file' ] );
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

		if ( ! is_wp_error( $term ) || ! is_null( $term ) ) {
			$activity_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->orbis_activities WHERE term_id = %d;", $term_id ) );

			// Format and data
			$format = [
				'name'        => '%s',
				'description' => '%s',
				'term_id'     => '%d',
			];

			$data = [
				'name'        => $term->name,
				'description' => $term->description,
				'term_id'     => $term_id,
			];

			// Activity
			if ( $activity_id ) {
				$result = $wpdb->update( $wpdb->orbis_activities, $data, [ 'id' => $activity_id ], $format );
			} else {
				$result = $wpdb->insert( $wpdb->orbis_activities, $data, $format );
			}
		}
	}

	public function admin_init() {
		// General
		add_settings_section(
			'orbis_timesheets_settings_general', // id
			__( 'General Settings', 'orbis-timesheets' ), // title
			'__return_false', // callback
			'orbis_timesheets_settings' // page
		);

		add_settings_field(
			'orbis_timesheets_registration_limit_lower', // id
			__( 'Registration Limit Lower', 'orbis-timesheets' ), // title
			[ $this, 'input_select' ], // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_general', // section
			[
				'label_for' => 'orbis_timesheets_registration_limit_lower',
				'options'   => [
					'0'       => __( 'None', 'orbis-timesheets' ),
					'1 day'   => __( '1 Day', 'orbis-timesheets' ),
					'3 days'  => __( '3 Days', 'orbis-timesheets' ),
					'1 week'  => __( '1 Week', 'orbis-timesheets' ),
					'1 month' => __( '1 Month', 'orbis-timesheets' ),
				],
			] // args
		);

		register_setting( 'orbis_timesheets', 'orbis_timesheets_registration_limit_lower' );

		add_settings_field(
			'orbis_timesheets_note', // id
			__( 'Note', 'orbis-timesheets' ), // title
			[ $this, 'input_text' ], // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_general', // section
			[
				'label_for' => 'orbis_timesheets_note',
			] // args
		);

		register_setting( 'orbis_timesheets', 'orbis_timesheets_note' );
	}

	/**
	 * Input text
	 *
	 * @param array $args
	 */
	public function input_text( $args ) {
		$name = $args['label_for'];

		$classes = [ 'regular-text' ];
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

		$classes = [];
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

		$classes = [];
		if ( isset( $args['classes'] ) ) {
			$classes = $args['classes'];
		}

		$options = [];
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
						( is_array( $current_value ) && in_array( $option_key, $current_value, true ) );

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
			__( 'Orbis Timesheets', 'orbis-timesheets' ),
			__( 'Timesheets', 'orbis-timesheets' ),
			'manage_options',
			'orbis_timesheets',
			[ $this, 'page_admin' ],
			'dashicons-clock',
			40
		);

		add_submenu_page(
			'orbis_timesheets', // parent_slug
			__( 'Orbis Timesheets Settings', 'orbis-timesheets' ), // page_title
			__( 'Settings', 'orbis-timesheets' ), // menu_title
			'manage_options', // capability
			'orbis_timesheets_settings', // menu_slug
			[ $this, 'page_settings' ] // function
		);

		global $submenu;

		/*
		 * Add taxonomy 'orbis_timesheets_activity' to sub menu
		 * @see https://github.com/WordPress/WordPress/blob/3.9.1/wp-admin/menu.php#L59
		 */
		if ( isset( $submenu['orbis_timesheets'] ) ) {
			$tax = get_taxonomy( 'orbis_timesheets_activity' );

			$submenu['orbis_timesheets'][] = [ // WPCS: override ok.
				esc_attr( $tax->labels->menu_name ),
				$tax->cap->manage_terms,
				add_query_arg( 'taxonomy', $tax->name, 'edit-tags.php' ),
			];
		}
	}

	public function page_admin() {
		include __DIR__ . '/../admin/page-timesheets.php';
	}

	public function page_settings() {
		include __DIR__ . '/../admin/page-settings.php';
	}
}
