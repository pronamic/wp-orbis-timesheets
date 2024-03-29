<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h2><?php echo get_admin_page_title(); ?></h2>

	<p>
		<?php

		printf(
			__( 'The Orbis Timesheets plugin requires an page with the shortcode %s.', 'orbis-timesheets' ), // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			'<code>[orbis_timesheets]</code>'
		);

		?>
	</p>

	<form action="options.php" method="post">
		<?php settings_fields( 'orbis_timesheets' ); ?>

		<?php do_settings_sections( 'orbis_timesheets_settings' ); ?>

		<?php submit_button(); ?>
	</form>
</div>
