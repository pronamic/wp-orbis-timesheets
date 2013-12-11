<?php

$user_id   = get_current_user_id();
$person_id = get_user_meta( $user_id, 'orbis_legacy_person_id', true );

$date = filter_input( INPUT_GET, 'date', FILTER_SANITIZE_STRING );

$action = filter_input( INPUT_GET, 'edit', FILTER_SANITIZE_STRING );

$registration_id = filter_input( INPUT_GET, 'work_registration', FILTER_SANITIZE_STRING );

$timestamp = strtotime( $date );
if ( ! $timestamp ) {
	$timestamp = time();
}

if ( empty( $registration_id ) ) {
	include 'timesheets_overview.php';
} else {
	include 'timesheets_edit.php';
}
