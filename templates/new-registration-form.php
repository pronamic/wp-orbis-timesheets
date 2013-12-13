<?php 

wp_enqueue_script( 'orbis-autocomplete' );
wp_enqueue_style( 'select2' );

// Errors
global $orbis_errors;

// Inputs
$entry = orbis_timesheets_get_entry_from_input();

?>
<div class="panel">
	<div class="content">
		<form action="" method="post">
			<?php wp_nonce_field( 'orbis_timesheets_add_new_registration', 'orbis_timesheets_new_registration_nonce' ); ?>

			<legend><?php _e( 'Add registration', 'orbis_timesheets' ); ?></legend>

			<?php include 'work_registration_fields.php'; ?>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="orbis_timesheets_add_registration"><?php esc_html_e( 'Register', 'orbis_timesheets' ); ?></button>
			</div>
		</form>
	</div>
</div>