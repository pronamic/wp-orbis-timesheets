<?php

function orbis_timesheets_can_register( $timestamp ) {
	$dateline_bottom = strtotime( 'midnight -3 days +10 hours' );

	$dateline_top = strtotime( 'midnight +3 days' );

	return ( $timestamp >= $dateline_bottom ) && ( $timestamp <= $dateline_top );
}

function get_edit_orbis_work_registration_link( $id ) {
	$link = add_query_arg( array(
		'work_registration' => $id,
		'action'            => 'edit'
	) );
	
	return $link;
}

function orbis_insert_timesheet_entry( $entry ) {
	$data   = array();
	$format = array();
		
	$data['created']   = date( 'Y-m-d H:i:s' );
	$format['created'] = '%s';
		
	$data['user_id']   = $entry->person_id;
	$format['user_id'] = '%d';
		
	$data['company_id']   = $entry->company_id;
	$format['company_id'] = '%d';
		
	if ( ! empty( $entry->project_id ) ) {
		$data['project_id']   = $entry->project_id;
		$format['project_id'] = '%d';
	}
		
	$data['activity_id']   = $entry->activity_id;
	$format['activity_id'] = '%d';
		
	$data['description']   = $entry->description;
	$format['description'] = '%s';
		
	$data['date']   = $entry->date;
	$format['date'] = '%s';
		
	$data['number_seconds']   = $entry->time;
	$format['number_seconds'] = '%d';

	$result = $wpdb->insert( $wpdb->orbis_timesheets, $data, $format );

	return $result;
}
