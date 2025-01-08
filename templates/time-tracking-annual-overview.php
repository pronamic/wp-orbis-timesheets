<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$date = new DateTimeImmutable();

if ( \array_key_exists( 'date', $_GET ) ) {
	$date_string = \sanitize_text_field( \wp_unslash( $_GET['date'] ) );

	try {
		$date_result = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date_string );

		if ( false === $date_result ) {
			throw new \Exception( 'Failed to parse date.' );
		}

		$date = $date_result->setTime( 0, 0 );
	} catch ( \Exception $e ) {
		\wp_die( $e->getMessage() );
	}
}

$report = get_orbis_timesheets_annual_report(
	[
		'date' => $date,
	]
);

require __DIR__ . '/time-tracking-annual-overview-style.php';

?>
<form class="mb-4 row gy-2 gx-3 align-items-center" method="get">
	<div class="col-auto">
		<label class="visually-hidden" for="date-input"><?php \esc_html_e( 'Date', 'orbis-timesheets' ); ?></label>
		<input name="date" type="date" class="form-control" id="date-input" value="<?php echo \esc_attr( $date->format( 'Y-m-d' ) ); ?>">
	</div>

	<div class="col-auto">
		<button type="submit" class="btn btn-primary"><?php \esc_html_e( 'Filter', 'orbis-timesheets' ); ?></button>
	</div>
</form>

<div class="card">
	<div class="card-body">

		<?php foreach ( $report->users as $user ) : ?>

			<h2><?php echo esc_html( $user->display_name ); ?></h2>

			<?php include __DIR__ . '/time-tracking-annual-overview-table.php'; ?>

		<?php endforeach; ?>

	</div>
</div>

<?php require __DIR__ . '/time-tracking-annual-overview-script.php'; ?>
