<?php

function orbis_timesheet_get_threshold_level( $total, $threshold ) {
	if ( null === $threshold && 0 === $total ) {
		return 0;
	}

	if ( 0 === $threshold && 0 === $total ) {
		return 0;
	}

	if ( $total >= $threshold ) {
		return 6;
	}

	if ( $total >= $threshold * 0.75 ) {
		return 5;
	}

	if ( $total >= $threshold * 0.5 ) {
		return 4;
	}

	return 0;
}

function get_orbis_timesheets_annual_report( $args ) {
	global $wpdb;

	$date = new DateTimeImmutable();

	if ( \array_key_exists( 'date', $args ) && $args['date'] instanceof DateTimeImmutable ) {
		$date = $args['date'];
	}

	$year = $date->format( 'Y' );

	$start_date = $date->setISODate( $year, 1, 1 );
	$end_date   = $date->modify( 'Last day of December this year' )->modify( '+1 day' )->modify( 'next monday' );

	$weeks = new DatePeriod( $start_date, new DateInterval( 'P1W' ), $end_date );

	$week_days = [
		0 => (object) [
			'label'       => '',
			'day_of_week' => 0,
		],
		1 => (object) [
			'label'       => 'ma',
			'day_of_week' => 1,
		],
		2 => (object) [
			'label'       => '',
			'day_of_week' => 2,
		],
		3 => (object) [
			'label'       => 'wo',
			'day_of_week' => 3,
		],
		4 => (object) [
			'label'       => '',
			'day_of_week' => 4,
		],
		5 => (object) [
			'label'       => 'vr',
			'day_of_week' => 5,
		],
		6 => (object) [
			'label'       => '',
			'day_of_week' => 6,
		],
	];

	/**
	 * Schedule.
	 */
	$schedule = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT
				CONCAT_WS( '-', schedule.user_id, schedule.date ) AS schedule_key,
				schedule.*
			FROM
				wp_orbis_timesheets_schedule AS schedule
			WHERE
				schedule.date >= %s
					AND
				schedule.date <= %s
			;
			",
			$start_date->format( 'Y-m-d' ),
			$end_date->format( 'Y-m-d' )
		),
		OBJECT_K
	);

	/**
	 * Users.
	 */
	$users = [
		'erik',
		'leooosterloo',
		'kj',
		'remco',
		'reuel',
	];

	if ( $year <= 2021 ) {
		$users[] = 'jelke';
	}

	if ( $year <= 2022 ) {
		$users[] = 'odilio';
	}

	if ( array_key_exists( 'user', $args ) && in_array( $args['user'], $users, true ) ) {
		$users = [ $args['user'] ];
	}

	$where = $wpdb->prepare(
		sprintf(
			'user.user_login IN ( %s )',
			implode( ',', array_fill( 0, count( $users ), '%s' ) )
		),
		$users
	);

	$query = "
		SELECT
			*
		FROM
			wp_users AS user
		WHERE
			$where
		;
	";

	$user_data = $wpdb->get_results( $query );

	/**
	 * Timesheet
	 */
	$query = $wpdb->prepare(
		"
		SELECT
			orbis_timesheet.user_id AS user_id,
			orbis_timesheet.date AS date,
			SUM( orbis_timesheet.number_seconds ) AS number_seconds
		FROM
			$wpdb->orbis_timesheets AS orbis_timesheet
		WHERE
			orbis_timesheet.date BETWEEN %s and %s
		GROUP BY
			orbis_timesheet.user_id, orbis_timesheet.date
		;
		",
		$start_date->format( 'Y-m-d' ),
		$end_date->format( 'Y-m-d' )
	);

	$timesheet_data = $wpdb->get_results( $query );

	/**
	 * Summaries.
	 */
	$users = [];

	foreach ( $user_data as $user ) {
		$object = (object) [
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'timesheet'    => [],
			'weeks'        => [],
		];

		$users[ $user->ID ] = $object;
	}

	foreach ( $timesheet_data as $item ) {
		if ( ! array_key_exists( $item->user_id, $users ) ) {
			continue;
		}

		$user = $users[ $item->user_id ];

		$user->timesheet[ $item->date ] = $item->number_seconds;
	}

	foreach ( $users as $user ) {
		foreach ( $weeks as $week ) {
			$days = [];

			foreach ( $week_days as $week_day ) {
				$date = $week->setISODate( $week->format( 'o' ), $week->format( 'W' ), $week_day->day_of_week );

				$total = 0;

				$key = $date->format( 'Y-m-d' );

				if ( array_key_exists( $key, $user->timesheet ) ) {
					$total = $user->timesheet[ $key ];
				}

				$threshold = 0;
				$classes   = [];

				$schedule_key = \sprintf(
					'%s-%s',
					$user->id,
					$date->format( 'Y-m-d' )
				);

				if ( array_key_exists( $schedule_key, $schedule ) ) {
					$item = $schedule[ $schedule_key ];

					$threshold = (int) $item->number_seconds;

					$classes[] = $item->class;
				}

				$days[ $week_day->day_of_week ] = (object) [
					'date'      => $date,
					'total'     => $total,
					'threshold' => $threshold,
					'classes'   => $classes,
				];
			}

			$key = $week->format( 'oW' );

			$object = (object) [
				'date'      => $week,
				'total'     => 0,
				'threshold' => 0,
				'days'      => $days,
			];

			foreach ( $object->days as $day ) {
				$object->total     += $day->total;
				$object->threshold += $day->threshold;
			}

			$user->weeks[ $key ] = $object;
		}
	}

	return (object) [
		'start_date' => $start_date,
		'end_date'   => $end_date,
		'query'      => $query,
		'weeks'      => $weeks,
		'week_days'  => $week_days,
		'users'      => $users,
	];
}
