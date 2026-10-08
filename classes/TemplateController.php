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
	public function __construct(
		/**
		 * Plugin.
		 */
		private $plugin
	) {
	}

	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		\add_filter( 'template_include', $this->template_include( ... ) );

		\add_filter( 'get_the_archive_title', $this->get_the_archive_title( ... ) );

		\add_filter( 'orbis_project_sections', $this->orbis_project_sections( ... ) );
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

		return match ( $route ) {
			'register' => $this->template_include_register( $template ),
			'annual_overview' => $this->template_include_annual_overview( $template ),
			'weekly_overview' => $this->template_include_weekly_overview( $template ),
			'weekly_report' => $this->template_include_weekly_report( $template ),
			'monthly_report' => $this->template_include_monthly_report( $template ),
			default => $template,
		};
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

		$sections[] = [
			'id'       => 'activities',
			'slug'     => \__( 'activities', 'orbis-timesheets' ),
			'name'     => \__( 'Activities', 'orbis-timesheets' ),
			'callback' => function (): void {
				include __DIR__ . '/../templates/project-activities-chart.php';
			},
		];

		$sections[] = [
			'id'       => 'persons',
			'slug'     => \__( 'persons', 'orbis-timesheets' ),
			'name'     => \__( 'Persons', 'orbis-timesheets' ),
			'callback' => function (): void {
				include __DIR__ . '/../templates/project-persons-chart.php';
			},
		];

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
