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

		\add_filter( 'get_the_archive_title', [ $this, 'get_the_archive_title' ] );

		\add_filter( 'orbis_project_sections', [ $this, 'orbis_project_sections' ] );
	}

	/**
	 * Template include.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	public function template_include( $template ) {
		$route = \get_query_var( 'orbis_timesheets_route', null );

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
			case 'weekly_report':
				return $this->template_include_weekly_report( $template );
			case 'monthly_report':
				return $this->template_include_monthly_report( $template );
			default:
				return $template;
		}
	}

	/**
	 * Get the archive title.
	 * 
	 * @link https://developer.wordpress.org/reference/functions/get_the_archive_title/
	 * @param string $title Title.
	 * @return string
	 */
	public function get_the_archive_title( $title ) {
		$route = \get_query_var( 'orbis_timesheets_route', null );

		if ( null === $route ) {
			return $title;
		}

		$title = \__( 'Timesheets', 'orbis-timesheets' );

		return $title;
	}

	/**
	 * Template include register.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include_register( $template ) {
		$template = __DIR__ . '/../templates/register.php';

		return $template;
	}

	/**
	 * Template include annual overview.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include_annual_overview( $template ) {
		$template = __DIR__ . '/../templates/annual-overview.php';

		return $template;
	}

	/**
	 * Template include weekly overview.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include_weekly_overview( $template ) {
		$template = __DIR__ . '/../templates/weekly-overview.php';

		return $template;
	}

	/**
	 * Template include weekly report.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include_weekly_report( $template ) {
		$template = __DIR__ . '/../templates/weekly-report.php';

		return $template;
	}

	/**
	 * Template include monthly report.
	 * 
	 * @param string $template Template.
	 * @return string
	 */
	private function template_include_monthly_report( $template ) {
		$template = __DIR__ . '/../templates/monthly-report.php';

		return $template;
	}

	/**
	 * Orbis project sections.
	 * 
	 * @param array $sections Sections.
	 * @return array
	 */
	public function orbis_project_sections( $sections ) {
		\array_unshift(
			$sections,
			[
				'id'       => 'timesheet',
				'slug'     => \__( 'timesheet', 'orbis-timesheets' ),
				'name'     => \__( 'Timesheet', 'orbis-timesheets' ),
				'callback' => [ $this, 'render_project_timesheet' ],
			] 
		);

		return $sections;
	}

	/**
	 * Render project timesheet.
	 * 
	 * @return void
	 */
	public function render_project_timesheet() {
		include __DIR__ . '/../templates/project-timesheet.php';
	}
}
