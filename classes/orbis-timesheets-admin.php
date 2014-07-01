<?php

class Orbis_Timesheets_Admin {
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'maybe_email_manually' ) );
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
		add_filter( sprintf( 'pre_update_option_%s', 'orbis_timesheets_email_frequency' ), array( $this, 'update_option_frequency' ), 10, 2 );

		add_settings_section(
			'orbis_timesheets_settings_email', // id
			__( 'E-mail Settings', 'orbis_timesheets' ), // title
			'__return_false', // callback
			'orbis_timesheets_settings' // page
		);

		$options = array( '' );
		foreach ( wp_get_schedules() as $name => $schedule ) {
			$options[ $name ] = $schedule['display'];
		}

		add_settings_field(
			'orbis_timesheets_email_frequency', // id
			__( 'Frequency', 'orbis_timesheets' ), // title
			array( $this, 'input_select' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email', // section
			array(
				'label_for' => 'orbis_timesheets_email_frequency',
				'options'   => $options,
			) // args
		);

		add_settings_field(
			'orbis_timesheets_email_time', // id
			__( 'Time', 'orbis_timesheets' ), // title
			array( $this, 'input_text' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email', // section
			array(
				'label_for' => 'orbis_timesheets_email_time',
				'classes'   => array(),
			) // args
		);

		add_settings_field(
			'orbis_timesheets_emails_next_schedule', // id
			__( 'Next Schedule', 'orbis_timesheets' ), // title
			array( $this, 'next_schedule' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email' // section
		);

		add_settings_field(
			'orbis_timesheets_email_subject', // id
			__( 'Subject', 'orbis_timesheets' ), // title
			array( $this, 'input_text' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email', // section
			array(
				'label_for' => 'orbis_timesheets_email_subject',
			) // args
		);

		$users        = get_users();
		$user_options = array();

		foreach ( $users as $user ) {

			$user_options[ $user->ID ] = $user->display_name;
		}

		add_settings_field(
			'orbis_timesheets_email_users', // id
			__( 'Receivers', 'orbis_timesheets' ), // title
			array( $this, 'input_select' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email', // section
			array(
				'label_for' => 'orbis_timesheets_email_users',
				'options'   => $user_options,
				'multiple'  => true,
			) // args
		);

		add_settings_field(
			'orbis_timesheets_email_manually', // id
			__( 'E-mail Manually', 'orbis_timesheets' ), // title
			array( $this, 'button_email_manually' ), // callback
			'orbis_timesheets_settings', // page
			'orbis_timesheets_settings_email', // section
			array(
				'label_for' => 'orbis_timesheets_email_subject',
			) // args
		);

		register_setting( 'orbis_timesheets', 'orbis_timesheets_email_frequency' );
		register_setting( 'orbis_timesheets', 'orbis_timesheets_email_time' );
		register_setting( 'orbis_timesheets', 'orbis_timesheets_email_subject' );
		register_setting( 'orbis_timesheets', 'orbis_timesheets_email_users' );
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

	public function button_email_manually() {
		submit_button(
			__( 'Send E-mail', 'orbis_timesheets' ),
			'secondary',
			'orbis_timesheets_email_manually',
			false
		);
	}

	public function next_schedule() {
		$timestamp = wp_next_scheduled( 'orbis_timesheets_emails' );

		if ( $timestamp ) {
			$timestamp = strtotime( get_date_from_gmt( '@' . $timestamp ) );

			echo date_i18n( 'D j M Y H:i:s', $timestamp );
		} else {
			_e( 'Not scheduled', 'orbis_timesheets' );
		}
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
				add_query_arg( 'taxonomy', $tax->name, 'edit-tags.php' )
			);
		}
	}

	public function page_admin() {
		$this->plugin->plugin_include( 'admin/page-timesheets.php' );
	}

	public function page_settings() {
		$this->plugin->plugin_include( 'admin/page-settings.php' );
	}

	public function update_option_frequency( $value ) {
		wp_clear_scheduled_hook( 'orbis_timesheets_emails' );

		if ( ! empty( $value ) ) {
			$time = get_gmt_from_date( get_option( 'orbis_timesheets_email_time' ), 'U' );

			wp_schedule_event( $time, $value, 'orbis_timesheets_emails' );
		}

		return $value;
	}

	public function maybe_email_manually() {
		if ( filter_has_var( INPUT_POST, 'orbis_timesheets_email_manually' ) ) {
			$this->plugin->email->send_timesheets_by_email();
		}
	}
}
