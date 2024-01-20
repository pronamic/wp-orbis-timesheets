<?php
/**
 * Rewrite controller
 *
 * @package Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

/**
 * Rewrite controller class
 */
class RewriteController {
	/**
	 * Construct rewrite controller.
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
		\add_filter( 'query_vars', [ $this, 'query_vars' ] );

		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Query vars.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/query_vars/
	 * @param string[] $query_vars Query vars.
	 * @return string[]
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'orbis_timesheets_route';

		return $query_vars;
	}

	/**
	 * Initialize.
	 * 
	 * @link https://make.wordpress.org/core/2015/10/07/add_rewrite_rule-accepts-an-array-of-query-vars-in-wordpress-4-4/
	 * @return void
	 */
	public function init() {
		\add_rewrite_rule(
			'tijdregistraties/registreren/?$', 
			[
				'orbis_timesheets_route' => 'register',
			],
			'top'
		);

		\add_rewrite_rule(
			'tijdregistraties/jaaroverzicht/?$', 
			[
				'orbis_timesheets_route' => 'annual_overview',
			],
			'top'
		);

		\add_rewrite_rule(
			'tijdregistraties/weekoverzicht/?$', 
			[
				'orbis_timesheets_route' => 'weekly_overview',
			],
			'top'
		);

		\add_rewrite_rule(
			'tijdregistraties/rapport/?$', 
			[
				'orbis_timesheets_route' => 'report',
			],
			'top'
		);

		\add_rewrite_rule(
			'tijdregistraties/maandrapport/?$', 
			[
				'orbis_timesheets_route' => 'monthly_report',
			],
			'top'
		);
	}
}
