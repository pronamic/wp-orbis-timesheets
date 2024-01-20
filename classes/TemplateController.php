<?php
/**
 * Template controller
 *
 * @package Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

/**
 * Template controller class
 */
class TemplateController {
	/**
	 * Construct template controller.
	 * 
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		\add_filter( 'template_include', [ $this, 'template_include' ] );
	}

	/**
	 * Template include.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include( $template ) {
		$route = get_query_var( 'orbis_timesheets_route', null );

		if ( null === $route ) {
			return $template;
		}

		switch ( $route ) {
			case 'register':
				return $this->template_include_register( $template );
			case 'annual_overview':
				return $this->template_include_annual_overview( $template );
			case 'weekly_overview':
				return $this->template_include_weekly_overview( $template );
			case 'report':
				return $this->template_include_report( $template );
			case 'monthly_report':
				return $this->template_include_monthly_report( $template );
			default:
				return $template;
		}
	}

	/**
	 * Template include register.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include_register( $template ) {
		return __DIR__ . '/../templates/register.php';
	}

	/**
	 * Template include annual overview.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include_annual_overview( $template ) {
		return __DIR__ . '/../templates/annual-overview.php';
	}

	/**
	 * Template include weekly overview.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include_weekly_overview( $template ) {
		return __DIR__ . '/../templates/weekly-overview.php';
	}

	/**
	 * Template include report.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include_report( $template ) {
		return __DIR__ . '/../templates/report.php';
	}

	/**
	 * Template include monthly report.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include_monthly_report( $template ) {
		return __DIR__ . '/../templates/monthly-report.php';
	}
}
