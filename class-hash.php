<?php
/**
 * Hash utilities for Cart Sharing and Saving.
 *
 * Provides helpers to generate short codes and unique cart hashes.
 *
 * @package EB\CSAS
 */

namespace EB\CSAS;

use EB\CSAS\Options;

/**
 * Provides functions to retrieve CSAS hashes.
 */
class Hash {

	/**
	 * Queries service used for DB lookups.
	 *
	 * @var \EB\CSAS\Queries
	 */
	private static $_queries; // phpcs:ignore

	/**
	 * Class constructor.
	 *
	 * @param \EB\CSAS\Queries $p_queries Queries service dependency.
	 * @return void
	 */
	public function __construct( $p_queries ) {

		self::$_queries = $p_queries;
	}

	/**
	 * Generate a 6-character short code consisting of digits and lowercase letters.
	 *
	 * @return string Generated short code.
	 */
	public function generate_short_code() {

		$_characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$_length     = strlen( $_characters );
		$_result     = '';

		for ( $_i = 0; $_i < 6; $_i++ ) {
			$_result .= $_characters[ wp_rand( 0, $_length - 1 ) ];
		}

		return $_result;
	}

	/**
	 * Generate a unique CSAS hash that does not exist in storage.
	 *
	 * Will attempt up to a maximum number of tries before returning the last
	 * generated value.
	 *
	 * @return string Unique CSAS hash.
	 */
	public function get_unique_cart_hash() {

		$_csas_hash           = '';
		$_exists              = false;
		$_max_number_of_tries = 20; // Prevent forever loop.

		do {

			// Generate a potential unique short code.
			$_csas_hash = $this->generate_short_code();

			// Check DB if hash already exists.
			$_stored_value = self::$_queries->get_row_exists_by_csas_hash( $_csas_hash );
			$_exists       = ! empty( $_stored_value );

			// Decrease the remaining attempts.
			--$_max_number_of_tries;

		} while ( $_exists && ( $_max_number_of_tries >= 0 ) ); // If it exists, loop again.

		return $_csas_hash;
	}
}
