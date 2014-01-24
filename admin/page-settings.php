<div class="wrap">
	<?php screen_icon( 'orbis_timesheets' ); ?>

	<h2><?php echo get_admin_page_title(); ?></h2>

	<p>
		<?php 
		
		printf(
			__( 'The Orbis Timesheets plugin requires an page with the shortcode %s.', 'orbis_timesheets' ),
			'<code>[orbis_timesheets]</code>'
		);

		?>
	</p>
</div>