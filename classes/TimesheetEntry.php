<?php
/**
 * Timesheet entry
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

use DateTime;

/**
 * Timesheet entry class
 */
class TimesheetEntry {
	public $date;

	public function __construct() {
		$this->date = new DateTime();
	}

	public function get_date() {
		return $this->date;
	}

	public function set_date( DateTime $date ) {
		$this->date = $date;
	}
}
