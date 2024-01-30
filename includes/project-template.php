<?php

function orbis_project_get_the_logged_time( $format = 'HH:MM' ) {
	global $post;

	$orbis_project  = new Pronamic\Orbis\Projects\Project( get_post() );
	$logged_seconds = $orbis_project->get_registered_seconds();

	$time = null;

	if ( isset( $logged_seconds ) ) {
		$time = orbis_time( $logged_seconds, $format );
	}

	return $time;
}

function orbis_project_the_logged_time( $format = 'HH:MM' ) {
	echo orbis_project_get_the_logged_time( $format );
}

function orbis_project_in_time() {
	global $post;

	$orbis_project  = new Pronamic\Orbis\Projects\Project( get_post() );
	$logged_seconds = $orbis_project->get_registered_seconds();

	$in_time = true;

	if ( isset( $logged_seconds, $post->project_number_seconds ) ) {
		$in_time = $logged_seconds <= $post->project_number_seconds;
	}

	return $in_time;
}
