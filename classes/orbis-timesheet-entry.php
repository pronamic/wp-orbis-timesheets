<?php

class Orbis_Timesheets_TimesheetEntry {
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
