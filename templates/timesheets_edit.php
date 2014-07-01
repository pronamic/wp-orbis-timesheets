<?php

wp_enqueue_script( 'orbis-autocomplete' );
wp_enqueue_style( 'select2' );

// Errors
global $orbis_errors;

$id = filter_input( INPUT_GET, 'entry_id', FILTER_SANITIZE_STRING );

$entry = orbis_timesheets_get_entry( $id );

if ( $entry ) : ?>

	<div class="panel">
		<div class="content">
			<form action="" method="post">
				<?php wp_nonce_field( 'orbis_timesheets_add_new_registration', 'orbis_timesheets_new_registration_nonce' ); ?>

				<legend><?php _e( 'Edit registration', 'orbis_timesheets' ); ?></legend>

				<?php include 'work_registration_fields.php'; ?>

				<div class="form-actions">
					<button type="submit" class="btn btn-primary" name="orbis_timesheets_add_registration"><?php esc_html_e( 'Edit', 'orbis_timesheets' ); ?></button>

					<?php

					$cancel_url = add_query_arg( array(
						'work_registration' => false,
						'action'            => false,
					) );

					?>
					<a class="btn btn-default" href="<?php echo esc_attr( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'orbis_timesheets' ); ?></a>
				</div>
			</form>
		</div>
	</div>

<?php endif; ?>
