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