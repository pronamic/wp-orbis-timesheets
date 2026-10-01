<?php
/**
 * Declarability
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Timesheets
 */

namespace Pronamic\Orbis\Timesheets;

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- False positive, `$this` is valid in enum methods.

/**
 * Declarability enum
 */
enum Declarability: string {
	case Chargeable    = 'chargeable';
	case NonChargeable = 'non_chargeable';
	case Excluded      = 'excluded';
	case Unknown       = 'unknown';
	case NotSet        = '';

	/**
	 * Get declarability from a database value.
	 *
	 * Empty values are mapped to `NotSet`, unrecognized values to `Unknown`.
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
			self::Chargeable    => \_x( 'Chargeable', 'declarability', 'orbis-timesheets' ),
			self::NonChargeable => \_x( 'Non-chargeable', 'declarability', 'orbis-timesheets' ),
			self::Excluded      => \_x( 'Excluded', 'declarability', 'orbis-timesheets' ),
			self::Unknown       => \_x( 'Unknown', 'declarability', 'orbis-timesheets' ),
			self::NotSet        => \_x( 'Not set', 'declarability', 'orbis-timesheets' ),
		};
	}

	/**
	 * Get Bootstrap badge class.
	 *
	 * @return string
	 */
	public function badge_class(): string {
		return match ( $this ) {
			self::Chargeable    => 'text-bg-success',
			self::NonChargeable => 'text-bg-secondary',
			self::Excluded      => 'text-bg-dark',
			self::Unknown       => 'text-bg-warning',
			self::NotSet        => 'text-bg-light border',
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
	 * Sum the number of seconds per declarability.
	 *
	 * @param iterable $items         Items with a `declarability` property.
	 * @param string   $seconds_field Property with the number of seconds.
	 * @return array<int, object{declarability: self, seconds: int}>
	 */
	public static function sum_seconds( iterable $items, string $seconds_field ): array {
		$totals = [];

		foreach ( self::cases() as $declarability ) {
			$totals[ $declarability->value ] = (object) [
				'declarability' => $declarability,
				'seconds'       => 0,
			];
		}

		foreach ( $items as $item ) {
			$declarability = self::from_value( $item->declarability );

			$totals[ $declarability->value ]->seconds += (int) $item->$seconds_field;
		}

		return \array_values(
			\array_filter(
				$totals,
				fn( $total ) => $total->seconds > 0
			)
		);
	}
}
