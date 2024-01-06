<?php

$report = get_orbis_timesheets_annual_report( $_GET );

require __DIR__ . '/time-tracking-annual-overview-style.php';

?>

<dl>
	<dt>Start Date</dt>
	<dd><?php echo esc_html( $report->start_date->format( 'Y-m-d' ) ); ?></dd>

	<dt>End Date</dt>
	<dd><?php echo esc_html( $report->end_date->format( 'Y-m-d' ) ); ?></dd>

	<?php if ( filter_input( INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN ) ) : ?>

		<dt>Query</dt>
		<dd><pre><?php echo esc_html( $report->query ); ?></pre></dd>

	<?php endif; ?>

</dl>

<div class="card">
	<div class="card-body">

		<?php foreach ( $report->users as $user ) : ?>

			<h2><?php echo esc_html( $user->display_name ); ?></h2>

			<?php include __DIR__ . '/time-tracking-annual-overview-table.php'; ?>

		<?php endforeach; ?>

	</div>
</div>

<?php require __DIR__ . '/time-tracking-annual-overview-script.php'; ?>
