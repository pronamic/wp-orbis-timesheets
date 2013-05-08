<?php

function orbis_project_get_the_logged_time( $format = 'H:m' ) {
	global $post;

	$time = null;

	if ( isset( $post->project_logged_time ) ) {
		$time = orbis_time( $post->project_logged_time, $format );
	}

	return $time;
}

function orbis_project_the_logged_time( $format = 'H:m' ) {
	echo orbis_project_get_the_logged_time( $format );
}

function orbis_project_in_time() {
	global $post;

	$in_time = true;

	if ( isset( $post->project_logged_time, $post->project_number_seconds ) ) {
		$in_time = $post->project_logged_time < $post->project_number_seconds;
	}

	return $in_time;
}
