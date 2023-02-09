<?php

global $wpdb;

$year = filter_input( INPUT_GET, 'year', FILTER_SANITIZE_STRING );

if ( empty( $year ) ) {
	$year = \date( 'Y' );
}

$start_date = new DateTimeImmutable( 'First monday of January ' . $year );
$end_date   = new DateTimeImmutable( 'Last day of December ' . $year );

$weeks = new DatePeriod( $start_date, new DateInterval( 'P1W' ), $end_date );

$week_days = array(
	0 => (object) array(
		'label'       => '',
		'day_of_week' => 0,
	),
	1 => (object) array(
		'label'       => 'ma',
		'day_of_week' => 1,
	),
	2 => (object) array(
		'label'       => '',
		'day_of_week' => 2,
	),
	3 => (object) array(
		'label'       => 'wo',
		'day_of_week' => 3,
	),
	4 => (object) array(
		'label'       => '',
		'day_of_week' => 4,
	),
	5 => (object) array(
		'label'       => 'vr',
		'day_of_week' => 5,
	),
	6 => (object) array(
		'label'       => '',
		'day_of_week' => 6,
	),
);

function orbis_timesheet_is_holiday( $date ) {
	$holidays = array(
		// Good Friday - https://en.wikipedia.org/wiki/Good_Friday
		'2021-04-02',
		// Easter Monday - https://en.wikipedia.org/wiki/Easter_Monday
		'2021-04-05',
		// Koningsdag - https://en.wikipedia.org/wiki/Koningsdag
		'2021-04-27',
		// Liberation Day (Netherlands) - https://en.wikipedia.org/wiki/Liberation_Day_(Netherlands)
		'2021-05-05',
		// Feast of the Ascension - https://en.wikipedia.org/wiki/Feast_of_the_Ascension
		'2021-05-13',
		// Pentecost - https://en.wikipedia.org/wiki/Pentecost
		'2021-05-24',
		// First Christmas Day - https://nl.wikipedia.org/wiki/Eerste_kerstdag
		'2021-12-25',
		// Boxing Day - https://nl.wikipedia.org/wiki/Tweede_kerstdag
		'2021-12-26',
		// New Year's Day - https://en.wikipedia.org/wiki/New_Year%27s_Day
		'2022-01-01',
		// Good Friday - https://en.wikipedia.org/wiki/Good_Friday
		'2022-04-15',
		// Easter Monday - https://en.wikipedia.org/wiki/Easter_Monday
		'2022-04-18',
		// Koningsdag - https://en.wikipedia.org/wiki/Koningsdag
		'2022-04-27',
		// Liberation Day (Netherlands) - https://en.wikipedia.org/wiki/Liberation_Day_(Netherlands)
		'2022-05-05',
		// Feast of the Ascension - https://en.wikipedia.org/wiki/Feast_of_the_Ascension
		'2022-05-26',
		// Pentecost - https://en.wikipedia.org/wiki/Pentecost
		'2022-06-06',
		// First Christmas Day - https://nl.wikipedia.org/wiki/Eerste_kerstdag
		'2022-12-25',
		// Boxing Day - https://nl.wikipedia.org/wiki/Tweede_kerstdag
		'2022-12-26',
		// New Year - https://en.wikipedia.org/wiki/New_Year
		'2023-01-01',
		// Good Friday - https://en.wikipedia.org/wiki/Good_Friday
		'2023-04-07',
		// Eerste paasdag - https://nl.wikipedia.org/wiki/Pasen
		'2023-04-09',
		// Tweede paasdag - https://nl.wikipedia.org/wiki/Pasen
		'2023-04-10',
		// Koningsdag - https://en.wikipedia.org/wiki/Koningsdag
		'2023-04-27',
		// Liberation Day (Netherlands) - https://en.wikipedia.org/wiki/Liberation_Day_(Netherlands)
		'2023-05-05',
		// Hemelvaartsdag - https://nl.wikipedia.org/wiki/Hemelvaartsdag
		'2023-05-18',
		// Eerste pinksterdag - https://nl.wikipedia.org/wiki/Pinksteren
		'2023-05-28',
		// Tweede pinksterdag - https://nl.wikipedia.org/wiki/Pinksteren
		'2023-05-09',
		// Eerste kerstdag - https://nl.wikipedia.org/wiki/Eerste_kerstdag
		'2023-12-25',
		// Tweede kerstdag - https://nl.wikipedia.org/wiki/Tweede_kerstdag
		'2023-12-26',
	);

	return in_array( $date->format( 'Y-m-d' ), $holidays, true );
}

function orbis_timesheet_get_day_threshold( $user, $date ) {
	if ( 'Saturday' === $date->format( 'l' ) ) {
		return null;
	}

	if ( 'Sunday' === $date->format( 'l' ) ) {
		return null;
	}

	if ( orbis_timesheet_is_holiday( $date ) ) {
		return 0;
	}

	if ( 'Tuesday' === $date->format( 'l' ) && 'reuel' === $user->username ) {
		return ( 4 * HOUR_IN_SECONDS );
	}

	if ( 'Thursday' === $date->format( 'l' ) && 'odilio' === $user->username ) {
		return null;
	}

	return 8 * HOUR_IN_SECONDS;
}

function orbis_timesheet_get_threshold_level( $total, $threshold ) {
	if ( null === $threshold && 0 === $total ) {
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

/**
 * Users.
 */
$users = array(
	'erikcordes',
	'leooosterloo',
	'kj',
	'remco',
	'reuel',
);

if ( $year <= 2021 ) {
	$users[] = 'jelke';
}

if ( $year <= 2022 ) {
	$users[] = 'odilio';
}

if ( array_key_exists( 'user', $_GET ) && in_array( $_GET['user'], $users, true ) ) {
	$users = [ $_GET['user'] ];
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
		orbis_hours_registration AS orbis_timesheet
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
$users = array();

foreach ( $user_data as $user ) {
	$object = (object) array(
		'id'           => $user->ID,
		'username'     => $user->user_login,
		'display_name' => $user->display_name,
		'timesheet'    => array(),
		'weeks'        => array(),
	);

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
		$days = array();

		foreach ( $week_days as $week_day ) {
			$date = $week->setISODate( $week->format( 'o' ), $week->format( 'W' ), $week_day->day_of_week );

			$total = 0;

			$key = $date->format( 'Y-m-d' );

			if ( array_key_exists( $key, $user->timesheet ) ) {
				$total = $user->timesheet[ $key ];
			}

			$days[ $week_day->day_of_week ] = (object) array(
				'date'      => $date,
				'total'     => $total,
				'threshold' => orbis_timesheet_get_day_threshold( $user, $date ),
			);
		}

		$key = $week->format( 'oW' );

		$object = (object) array(
			'date'      => $week,
			'total'     => 0,
			'threshold' => 0,
			'days'      => $days,
		);

		foreach ( $object->days as $day ) {
			$object->total     += $day->total;
			$object->threshold += $day->threshold;
		}

		$user->weeks[ $key ] = $object;
	}
}

?>
<style type="text/css">
	body {
		font-family: sans-serif;
	}

	.orbis-timesheet-table {
		border-collapse: separate;
		border-spacing: 5px;
	}

	.orbis-timesheet-table td {
		text-align: center;
	}

	.orbis-timesheet-day {
		background: rgb( 235, 237, 240 );

		border: 1px solid rgba( 27, 31, 35, 0.06 );

		display: block;

		width: 20px;
		height: 20px;
	}

	/**
	 * Levels like GitHub.
	 *
	 * @link https://www.schemecolor.com/red-orange-green-gradient.php
	 */
	.orbis-timesheet-level-0 {

	}

	.orbis-timesheet-level-4 {
		background-color: #FAB733;
	}

	.orbis-timesheet-level-5 {
		background-color: #ACB334;
	}

	.orbis-timesheet-level-6 {
		background-color: #69B34C;
	}

	.orbis-timesheet-level-holiday {
		border-color: Red;
	}

</style>

<dl>
	<dt>Start Date</dt>
	<dd><?php echo esc_html( $start_date->format( 'Y-m-d' ) ); ?></dd>

	<dt>End Date</dt>
	<dd><?php echo esc_html( $end_date->format( 'Y-m-d' ) ); ?></dd>

	<?php if ( filter_input( INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN ) ) : ?>

		<dt>Query</dt>
		<dd><pre><?php echo esc_html( $query ); ?></pre></dd>

	<?php endif; ?>

</dl>

<div class="card">
	<div class="card-body">

		<?php foreach ( $users as $user ) :?>

			<h2><?php echo esc_html( $user->display_name ); ?></h2>

			<table class="orbis-timesheet-table">
				<thead>
					<tr>
						<th scope="col"></th>

						<?php foreach ( $weeks as $week ) : ?>

							<th scope="col">
								<?php echo esc_html( $week->format( 'W' ) ); ?>
							</th>

						<?php endforeach; ?>
					</tr>
				</thead>

				<tbody>

					<?php foreach ( $week_days as $week_day ) : ?>

						<tr>
							<?php

							$days = array();

							foreach ( $user->weeks as $week ) {
								$day = $week->days[ $week_day->day_of_week ];

								$level = orbis_timesheet_get_threshold_level( $day->total, $day->threshold );

								if ( orbis_timesheet_is_holiday( $day->date ) ) {
									$level = 'holiday';
								}

								$days[] = (object) array(
									'date'  => $day->date,
									'tippy' => sprintf(
										'%s - %s / %s',
										$day->date->format( 'D j M' ),
										orbis_time( $day->total ),
										orbis_time( $day->threshold )
									),
									'url'   => add_query_arg(
										'date',
										$day->date->format( 'Y-m-d' ),
										home_url( '/werk/' )
									),
									'level' => $level,
								);
							}

							?>
							<th scope="row"><?php echo \esc_html( $week_day->label ); ?></th>

							<?php foreach ( $days as $day ) : ?>

								<td data-tippy-content="<?php echo esc_attr( $day->tippy ); ?>">
									<a class="orbis-timesheet-day orbis-timesheet-level-<?php echo esc_attr( $day->level ); ?>" href="<?php echo esc_url( $day->url ); ?>"></a>
								</td>

							<?php endforeach; ?>
						</tr>

					<?php endforeach; ?>

				</tbody>

				<tfoot>
					<tr>
						<th scope="row">Σ</th>

						<?php foreach ( $user->weeks as $week ) : ?>

							<?php

							$tippy = sprintf(
								'Week %s - %s / %s',
								$week->date->format( 'W' ),
								orbis_time( $week->total ),
								orbis_time( $week->threshold )
							);

							$level = orbis_timesheet_get_threshold_level( $week->total, $week->threshold );

							$sunday = $week->date->modify( '-1 day' );

							$url = add_query_arg(
								'date',
								$sunday->format( 'd-m-Y' ),
								home_url( '/rapporten/werk/week/' )
							);

							?>
							<td data-tippy-content="<?php echo esc_attr( $tippy ); ?>">
								<a class="orbis-timesheet-day orbis-timesheet-level-<?php echo esc_attr( $level ); ?>" href="<?php echo esc_url( $url ); ?>"></a>
							</td>

						<?php endforeach; ?>
					</tr>
				</tfoot>
			</table>

		<?php endforeach; ?>

	</div>
</div>

<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
	tippy( '[data-tippy-content]' );
</script>
