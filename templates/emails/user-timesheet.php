<?php do_action( 'orbis_email_header' ); ?>

<table style="width: 100%; max-width: 100%; margin-bottom: 20px; border: 1px solid #ddd; ">
	<thead>
		<tr>

			<th scope="col" style="text-align: left; border: 1px solid #ddd;">
				<?php _e( 'User', 'orbis_timesheets' ); ?>
			</th>

			<?php foreach ( $dates as $date => $null ) : ?>

				<th scope="col" style="text-align: left; border: 1px solid #ddd;">
					<?php echo date( 'D j M', strtotime( $date ) ); ?>
				</th>

			<?php endforeach; ?>

		</tr>
	</thead>

	<tbody>

		<?php foreach ( $timesheets as $user_id => $timesheet ) : ?>

			<tr>
				<td scope="row" style="text-align: left; border: 1px solid #ddd;">
					<?php echo get_the_author_meta( 'display_name', $user_id ); ?>
				</td>

				<?php foreach ( $timesheet as $date => $time ) : ?>

					<?php

					$url = add_query_arg( array(
						'start_date' => date( 'Y-m-d', strtotime( $date ) ),
						'end_date'   => date( 'Y-m-d', strtotime( $date ) ),
						'user'       => $user_id,
					) );

					?>

					<td scope="row" style="text-align: left; border: 1px solid #ddd;">
						<?php echo orbis_time( $time ); ?>
					</td>

				<?php endforeach; ?>

			</tr>

	<?php endforeach; ?>

	</tbody>
</table>

<?php do_action( 'orbis_email_footer' ); ?>
