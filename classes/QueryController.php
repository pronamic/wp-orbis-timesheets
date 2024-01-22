<?php
/**
 * Query controller
 *
 * @package Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

use WP_Query;

/**
 * Query controller class
 */
class QueryController {
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
		\add_filter( 'posts_clauses', [ $this, 'posts_clauses' ], 20, 2 );
	}

	/**
	 * Posts clauses.
	 * 
	 * @param array.   $pieces Query pieces.
	 * @param WP_Query $query  WordPress query.
	 * @return array
	 */
	public function posts_clauses( $pieces, $query ) {
		global $wpdb;

		$post_type = $query->get( 'post_type' );

		if ( 'orbis_project' == $post_type ) {
			// Fields
			$fields = ',
				SUM( logged_time.number_seconds ) AS project_logged_time
			';

			// Join
			$join = "
				LEFT JOIN
					$wpdb->orbis_timesheets AS logged_time
						ON logged_time.project_id = project.id
			";

			// Group by
			$groupby  = '';
			$groupby .= empty( $pieces['groupby'] ) ? '' : ', ';
			$groupby .= 'project.id';

			// Pieces
			$pieces['join']    .= $join;
			$pieces['fields']  .= $fields;
			$pieces['groupby'] .= $groupby;
		}

		return $pieces;
	}
}
