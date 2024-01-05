<?php

$user_id = get_current_user_id();

$date     = filter_input( INPUT_GET, 'date', FILTER_SANITIZE_STRING );
$action   = filter_input( INPUT_GET, 'edit', FILTER_SANITIZE_STRING );
$entry_id = filter_input( INPUT_GET, 'entry_id', FILTER_SANITIZE_STRING );

$timestamp = strtotime( $date );
if ( ! $timestamp ) {
	$timestamp = time();
}

// Activities
global $wpdb;

$query = "
	SELECT
		*
	FROM
		$wpdb->orbis_activities
	ORDER BY
		name
	;		
";

$activities = $wpdb->get_results( $query );

// View
if ( empty( $entry_id ) ) {
	include 'timesheets_overview.php';
} else {
	include 'timesheets_edit.php';
}
