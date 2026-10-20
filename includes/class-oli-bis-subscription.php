<?php
/**
 * Subscription data object.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Immutable-ish row wrapper for a waitlist subscription.
 *
 * @since 0.1.0
 */
class Oli_BIS_Subscription {

	/**
	 * Row data.
	 *
	 * @var array<string,mixed>
	 */
	private $data = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $data Row data.
	 */
	public function __construct( array $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Returns a field.
	 *
	 * @since 0.1.0
	 * @param string $key     Field name.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Returns the numeric ID.
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_id() {
		return absint( $this->get( 'id', 0 ) );
	}

	/**
	 * Returns the product ID (parent for variations).
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->get( 'product_id', 0 ) );
	}

	/**
	 * Returns the variation ID.
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->get( 'variation_id', 0 ) );
	}

	/**
	 * Returns the product used for stock/email (variation when present).
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_target_product_id() {
		$variation_id = $this->get_variation_id();

		return $variation_id ? $variation_id : $this->get_product_id();
	}

	/**
	 * Returns the subscriber email.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_email() {
		return (string) $this->get( 'email', '' );
	}

	/**
	 * Returns the status.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_status() {
		return (string) $this->get( 'status', '' );
	}

	/**
	 * Returns the stored language/locale.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_language() {
		return (string) $this->get( 'language', '' );
	}

	/**
	 * Returns decoded variation attributes.
	 *
	 * @since 0.1.0
	 * @return array<string,string>
	 */
	public function get_attributes() {
		$raw = $this->get( 'attributes', '' );

		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Returns all row data.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return $this->data;
	}
}
