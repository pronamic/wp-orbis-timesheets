<?php

$today = new DateTime();

?>
<table class="orbis-timesheet-table">
	<thead>
		<tr>
			<th scope="col"></th>

			<?php foreach ( $report->weeks as $week ) : ?>

				<th scope="col">
					<?php echo esc_html( $week->format( 'W' ) ); ?>
				</th>

			<?php endforeach; ?>
		</tr>
	</thead>

	<tbody>

		<?php foreach ( $report->week_days as $week_day ) : ?>

			<tr>
				<?php

				$days = array();

				foreach ( $user->weeks as $week ) {
					$day = $week->days[ $week_day->day_of_week ];

					$level = orbis_timesheet_get_threshold_level( $day->total, $day->threshold );

					if ( orbis_timesheet_is_holiday( $day->date ) ) {
						$level = 'holiday';
					}

					$classes = [
						'orbis-timesheet-level-' . $level,
					];

					if ( $day->date->format( 'Ymd' ) == $today->format( 'Ymd' ) ) {
						$classes[] = 'border';
						$classes[] = 'border-dark';
					}

					if ( isset( $selected ) && $day->date->format( 'Ymd' ) == $selected->format( 'Ymd' ) ) {
						$classes[] = 'border';
						$classes[] = 'border-primary';
					}

					$classes = array_unique( $classes );

					$days[] = (object) array(
						'date'    => $day->date,
						'tippy'   => sprintf(
							'%s - %s / %s',
							$day->date->format( 'D j M' ),
							orbis_time( $day->total ),
							orbis_time( $day->threshold )
						),
						'url'     => add_query_arg(
							'date',
							$day->date->format( 'Y-m-d' ),
							home_url( '/werk/' )
						),
						'classes' => $classes,
					);
				}

				?>
				<th scope="row"><?php echo \esc_html( $week_day->label ); ?></th>

				<?php foreach ( $days as $day ) : ?>

					<td data-tippy-content="<?php echo esc_attr( $day->tippy ); ?>">
						<a class="orbis-timesheet-day <?php echo esc_attr( implode( ' ', $day->classes ) ); ?>" href="<?php echo esc_url( $day->url ); ?>"></a>
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
