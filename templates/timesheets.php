<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

$date     = orbis_timesheets_filter_text_input( INPUT_GET, 'date' );
$action   = orbis_timesheets_filter_text_input( INPUT_GET, 'edit' );
$entry_id = orbis_timesheets_filter_int_input( INPUT_GET, 'entry_id' );

$timestamp = ( null === $date ) ? false : strtotime( $date );
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
