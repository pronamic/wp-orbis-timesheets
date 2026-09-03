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
	/**
	 * Identifier.
	 *
	 * @var int|string|null
	 */
	public $id;

	/**
	 * Company ID.
	 *
	 * @var int|string|null
	 */
	public $company_id;

	/**
	 * Company name.
	 *
	 * @var string|null
	 */
	public $company_name;

	/**
	 * Project ID.
	 *
	 * @var int|string|null
	 */
	public $project_id;

	/**
	 * Project name.
	 *
	 * @var string|null
	 */
	public $project_name;

	/**
	 * Subscription ID.
	 *
	 * @var int|string|null
	 */
	public $subscription_id;

	/**
	 * Subscription name.
	 *
	 * @var string|null
	 */
	public $subscription_name;

	/**
	 * Activity ID.
	 *
	 * @var int|string|null
	 */
	public $activity_id;

	/**
	 * Description.
	 *
	 * @var string|null
	 */
	public $description;

	/**
	 * Date.
	 *
	 * @var DateTime
	 */
	public $date;

	/**
	 * Time in seconds.
	 *
	 * @var int|string|null
	 */
	public $time;

	/**
	 * User ID.
	 *
	 * @var int|null
	 */
	public $user_id;

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
