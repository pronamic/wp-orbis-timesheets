<?php

function orbis_timesheets_posts_clauses( $pieces, $query ) {
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

add_filter( 'posts_clauses', 'orbis_timesheets_posts_clauses', 20, 2 );
