<?php if ( isset( $users_registered_hours ) && is_array( $users_registered_hours ) &&
		   isset( $dates ) && is_array( $dates ) ) : ?>

<?php

$dates_keys = array_keys( $dates );

?>

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

	<?php foreach ( $users_registered_hours as $user_id => $registered_hours ) : ?>

	<tr>

		<td scope="row" style="text-align: left; border: 1px solid #ddd;">
			<?php echo get_the_author_meta( 'display_name', $user_id ); ?>
		</td>

		<?php $i = 0; foreach ( $registered_hours as $registered_hour ) : ?>

		<?php

		$url = add_query_arg( array(
			'start_date' => date( 'Y-m-d', strtotime( $dates_keys[ $i ] ) ),
			'end_date'   => date( 'Y-m-d', strtotime( $dates_keys[ $i ] ) ),
			'user'       => $user_id,
		) );

		?>

		<td scope="row" style="text-align: left; border: 1px solid #ddd;">
			<?php echo orbis_time( $registered_hour ); ?>
		</td>

		<?php $i++; endforeach; ?>

	</tr>

	<?php endforeach; ?>

	</tbody>

</table>

<?php endif; ?>