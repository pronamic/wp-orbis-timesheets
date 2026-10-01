<?php
/**
 * Billability
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- False positive, `$this` is valid in enum methods.

/**
 * Billability enum
 */
enum Billability: string {
	case Billable    = 'billable';
	case NonBillable = 'non_billable';
	case Excluded    = 'excluded';
	case Unknown     = 'unknown';
	case Undefined   = '';

	/**
	 * Get billability from a database value.
	 *
	 * Empty values are mapped to `Undefined`, unrecognized values to `Unknown`.
	 *
	 * @param string|null $value Value.
	 * @return self
	 */
	public static function from_value( $value ): self {
		return self::tryFrom( (string) $value ) ?? self::Unknown;
	}

	/**
	 * Get label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::Billable    => \_x( 'Billable', 'billability', 'orbis-timesheets' ),
			self::NonBillable => \_x( 'Non-billable', 'billability', 'orbis-timesheets' ),
			self::Excluded      => \_x( 'Excluded', 'billability', 'orbis-timesheets' ),
			self::Unknown       => \_x( 'Unknown', 'billability', 'orbis-timesheets' ),
			self::Undefined     => \_x( 'Undefined', 'billability', 'orbis-timesheets' ),
		};
	}

	/**
	 * Get Bootstrap badge class.
	 *
	 * @return string
	 */
	public function badge_class(): string {
		return match ( $this ) {
			self::Billable    => 'text-bg-success',
			self::NonBillable => 'text-bg-secondary',
			self::Excluded      => 'text-bg-dark',
			self::Unknown       => 'text-bg-warning',
			self::Undefined     => 'text-bg-light border',
		};
	}

	/**
	 * Get Bootstrap badge HTML.
	 *
	 * @return string
	 */
	public function badge(): string {
		return \sprintf(
			'<span class="badge %s">%s</span>',
			\esc_attr( $this->badge_class() ),
			\esc_html( $this->label() )
		);
	}

	/**
	 * Sum the number of seconds per billability.
	 *
	 * @param iterable $items         Items with a `billability` property.
	 * @param string   $seconds_field Property with the number of seconds.
	 * @return array<int, object{billability: self, seconds: int}>
	 */
	public static function sum_seconds( iterable $items, string $seconds_field ): array {
		$totals = [];

		foreach ( self::cases() as $billability ) {
			$totals[ $billability->value ] = (object) [
				'billability' => $billability,
				'seconds'     => 0,
			];
		}

		foreach ( $items as $item ) {
			$billability = self::from_value( $item->billability );

			$totals[ $billability->value ]->seconds += (int) $item->$seconds_field;
		}

		return \array_values(
			\array_filter(
				$totals,
				fn( $total ) => $total->seconds > 0
			)
		);
	}
}
