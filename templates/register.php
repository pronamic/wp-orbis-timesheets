<?php
/**
 * Register template
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Tasks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

?>
<div>
	<?php require __DIR__ . '/timesheets.php'; ?>
</div>
<?php

get_footer();