<?php

function orbis_timesheets_posts_clauses( $pieces, $query ) {
	global $wpdb;

	$post_type = $query->get( 'post_type' );

	if ( $post_type == 'orbis_project' ) {
		$fields = ",
			SUM( logged_time.number_seconds ) AS project_logged_time
		";

		$join = "
			LEFT JOIN
				orbis_hours_registration AS logged_time
					ON logged_time.project_id = project.id
		";

		$groupby = "
			project.id		
		";

		$pieces['join']    .= $join;
		$pieces['fields']  .= $fields;
		$pieces['groupby'] .= $groupby;
	}

	return $pieces;
}

add_filter( 'posts_clauses', 'orbis_timesheets_posts_clauses', 20, 2 );