<h2><?php esc_html_e( 'Timesheets', 'orbis-timesheets' ); ?></h2>

<table style="width: 100%; max-width: 100%;" cellpadding="5">
	<thead>
		<tr>
			<th scope="col" style="text-align: left;">
				<?php _e( 'User', 'orbis-timesheets' ); ?>
			</th>

			<?php foreach ( $dates as $date => $null ) : ?>

				<th scope="col" style="text-align: left;">
					<?php echo esc_html( date_i18n( 'D j M', strtotime( $date ) ) ); ?>
				</th>

			<?php endforeach; ?>

		</tr>
	</thead>

	<tbody>

		<?php foreach ( $timesheets as $user_id => $timesheet ) : ?>

			<tr>
				<td scope="row" style="text-align: left;">
					<?php echo get_the_author_meta( 'display_name', $user_id ); ?>
				</td>

				<?php foreach ( $timesheet as $date => $time ) : ?>

					<?php

					$url = add_query_arg(
						[
							'start_date' => date( 'Y-m-d', strtotime( $date ) ),
							'end_date'   => date( 'Y-m-d', strtotime( $date ) ),
							'user'       => $user_id,
						] 
					);

					?>

					<td scope="row" style="text-align: left;">
						<?php echo esc_html( orbis_time( $time ) ); ?>
					</td>

				<?php endforeach; ?>

			</tr>

	<?php endforeach; ?>

	</tbody>
</table>
