<?php
/**
 * The main class for all CSAS Queries.
 *
 * @package EBCSAS
 * @param string $p_csas_hash Cart hash.
 * @param string $p_hash Cart hash.
 * @param string $p_csas_cart_hash CSAS cart hash.
 * @param string $p_wc_cart_hash WooCommerce cart hash.
 * @param int $p_number_of_hours Number of hours before expiry.
 */

namespace EB\CSAS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin's queries class.
 */
class Queries {

	/**
	 * Class constructor.
	 */
	public function __construct() {
	}

	/**
	 * Inserts basic CSAS admin options if not already set.
	 */
	public function insert_basic_options() {

		global $wpdb;

		$_no_csas_options = $wpdb->get_var( 'select 1 from {$wpdb->prefix}options where option_name = _csas_admin' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( empty( $_no_csas_options ) ) {
			$_values = array(
				'allow_saving'             => '1',
				'allow_sharing'            => '1',
				'allow_guests_ops'         => '1',
				'use_wc_style_for_buttons' => '1',
			);
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$_serialized = serialize( $_values );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}options (option_name,option_value) values ('_csas_admin',%s)", $_serialized ) );
		}
	}
	/**
	 * Returns 1 if there is a saved cart for the given hash.
	 *
	 * @param [type] $p_csas_hash Cart Hash.
	 * @return mixed
	 */
	public function get_row_exists_by_csas_hash( $p_csas_hash ) {

		global $wpdb;

		$_val = $wpdb->get_var( $wpdb->prepare( "select 1 from {$wpdb->prefix}custom_csas_data where csas_hash = %s", $p_csas_hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $_val;
	}

	/**
	 * Gets cart data from DB for given session ID.
	 *
	 * @param [type] $p_session_id WC session ID.
	 * @return mixed
	 */
	public function get_cart_data_for_wc_session( $p_session_id = null ) {

		global $wpdb;

		$_data = $wpdb->get_results( $wpdb->prepare( "select csas_hash, object_id, updated_at, items, coupons, totals from {$wpdb->prefix}custom_csas_data where wc_session_id = %s", $p_session_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $_data ?? array();
	}

	/**
	 * Returns cart data for given user ID.
	 *
	 * @param [type] $p_user_id WP user ID.
	 * @param array  $p_args Currenty supports 'allow expired'.
	 * @return mixed
	 */
	public function get_cart_data_for_user( $p_user_id, $p_args = array() ) {

		global $wpdb;

		$_allow_expired = $p_args['allow_expired'] ?? false;

		$_data = $wpdb->get_results( $wpdb->prepare( "select csas_hash, object_id, updated_at, items, coupons, totals from {$wpdb->prefix}custom_csas_data where type = 1 AND object_id = %s and expired IN (%s)", $p_user_id, ( ! $_allow_expired ) ? '0' : '0,1' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $_data;
	}
	/**
	 * Returns cart data for the given cart hash.
	 *
	 * @param [type]  $p_hash Cart hash.
	 * @param boolean $p_allow_expired Description.
	 * @return mixed
	 */
	public function get_cart_data_for_csas_hash( $p_hash, $p_allow_expired = true ) {

		global $wpdb;

		$_data = $wpdb->get_results( $wpdb->prepare( "SELECT object_id, wc_cart_hash, type, items, coupons, totals, updated_at from {$wpdb->prefix}custom_csas_data WHERE csas_hash = %s and expired IN (%s)", $p_hash, ( ! $p_allow_expired ) ? '0' : '0,1' ) )[0] ?? array(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $_data;
	}

	/**
	 * Returns CSAS cart hash for the given user id, session id, and WC Cart hash.
	 *
	 * @param [type] $p_user_id WP user ID.
	 * @param [type] $p_wc_hash WC cart hash.
	 * @param [type] $p_wc_session_id WC session ID.
	 * @return mixed
	 */
	public function get_csas_hash( $p_user_id, $p_wc_hash, $p_wc_session_id = null ) {

		global $wpdb;

		$_result    = $wpdb->get_var( $wpdb->prepare( "select csas_hash from {$wpdb->prefix}custom_csas_data where object_id = %s and wc_session_id = %s and wc_cart_hash = %s", $p_user_id, $p_wc_session_id, $p_wc_hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$_csas_hash = '';

		if ( ! empty( $_result ) ) {
			$_csas_hash = $_result;
		}

		return $_csas_hash;
	}

	/**
	 * Inserts a new cart to the DB
	 *
	 * @param [type]  $csas_hash The CSAS cart hash.
	 * @param [type]  $p_wc_cart_hash WC cart hash.
	 * @param [type]  $p_session_id WC session ID.
	 * @param [type]  $p_product_ids products IDs array.
	 * @param [type]  $p_updated_at updating time.
	 * @param [type]  $p_items Array of items.
	 * @param [type]  $p_coupons Array of coupons.
	 * @param [type]  $p_totals Array of WC cart totals.
	 * @param integer $p_type type of entity the cart belongs to.
	 * @param [type]  $object_id object ID, currently user ID.
	 * @return mixed
	 */
	public function insert_csas_data( $csas_hash, $p_wc_cart_hash, $p_session_id, $p_product_ids, $p_updated_at = null, $p_items = null, $p_coupons = null, $p_totals = null, $p_type = 0, $object_id = null ) {

		global $wpdb;
		$_main_table_name = \EB\CSAS\DB\get_carts_table_name();
		$_meta_table_name = \EB\CSAS\DB\get_carts_meta_table_name();

		// TODO: should it be here?
		//
		$_product_ids_str = '';
		foreach ( $p_product_ids as $line_data ) {
			$_product_ids_str .= $line_data['pid'] . ':' . $line_data['vid'] . ',';
		}

		$_values = array(
			'csas_hash'     => $csas_hash,
			'wc_cart_hash'  => sanitize_text_field( $p_wc_cart_hash ),
			'wc_session_id' => $p_session_id,
			'type'          => sanitize_text_field( $p_type ),
			'object_id'     => absint( $object_id ),
			'product_ids'   => $_product_ids_str,
			'updated_at'    => $p_updated_at,
		);

		$_formats = array( '%s', '%s', '%s', '%s', '%d', '%s' );

		$_optionals_values = array( 'items', 'coupons', 'totals' );
		foreach ( $_optionals_values as $optional_value ) {
			$_var_name = 'p_' . $optional_value;
			$_var      = ${ $_var_name };

			if ( ! empty( $_var ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
								$_values[ $optional_value ] = maybe_serialize( $_var );
				$_formats[]                                 = '%s';
			}
		}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$res = $wpdb->insert(
			$_main_table_name,
			$_values,
			$_formats
		);

		// Insert succeeded.
		//

		// TODO: make a bulk insert.
		//
		if ( $res > 0 ) {
			$_cart_id = $wpdb->insert_id;

			// Insert meta.
			//
			foreach ( $p_product_ids as $line_data ) {

				$_val = ! empty( $line_data['vid'] ?? '' ) ? $line_data['vid'] : $line_data['pid'];

				$_values = array(
					'cart_id'    => $_cart_id,
					'meta_key'   => 'pid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $_val, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				);

				$_formats = array( '%d', '%s', '%d' );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$res = $wpdb->insert(
					$_meta_table_name,
					$_values,
					$_formats
				);
			}
		}
	}

	/**
	 * Deletes cart from DB by the given CSAS cart hash.
	 *
	 * @param [type] $p_csas_cart_hash CSAS cart hash.
	 * @return mixed
	 */
	public function delete_csas_data_by_csas_cart_hash( $p_csas_cart_hash ) {

		global $wpdb;
		$_table_name      = \EB\CSAS\DB\get_carts_table_name();
		$_meta_table_name = \EB\CSAS\DB\get_carts_meta_table_name();

		$_cart_id = $wpdb->get_var( $wpdb->prepare( "select id from {$wpdb->prefix}custom_csas_data where csas_hash = %s", $p_csas_cart_hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$_carts_query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}custom_csas_data WHERE id = %s", $_cart_id );

		$_meta_table_query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}custom_csas_metadata WHERE cart_id = %s", $_cart_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $_carts_query );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $_meta_table_query );

		return true;
	}

	/**
	 * Deletes cart from DB by the given WC cart hash.
	 *
	 * @param [type] $p_wc_cart_hash WooCommerce cart hash.
	 * @return mixed
	 */
	public function delete_csas_data_by_wc_cart_hash( $p_wc_cart_hash ) {

		global $wpdb;
		$_table_name = \EB\CSAS\DB\get_carts_table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}custom_csas_data WHERE wc_cart_hash = %s", $p_wc_cart_hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Get the user ID associated with the given CSAS hash.
	 *
	 * @param [type] $p_csas_hash CSAS hash.
	 * @return mixed
	 */
	public function get_user_id_by_csas_hash( $p_csas_hash ) {

		global $wpdb;

		$_object_id = $wpdb->get_var( $wpdb->prepare( "select object_id from {$wpdb->prefix}custom_csas_data where csas_hash = %s", $p_csas_hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $_object_id;
	}

	/**
	 * Gets all the newly expired carts.
	 *
	 * @param [type] $p_number_of_hours number of hours before the cart is expired.
	 * @return mixed
	 */
	public function get_newly_expired_rows( $p_number_of_hours ) {

		global $wpdb;

		// Table name with WordPress prefix.
		//
		$_table_name = \EB\CSAS\DB\get_carts_table_name();

		// Fetch the rows that meet the condition.
		//
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$_expired_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}custom_csas_data WHERE `updated_at` + INTERVAL %d HOUR <= NOW() AND expired = 0;",
				$p_number_of_hours
			)
		);

		return $_expired_rows;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $p_number_of_hours Number of hours.
	 * @return mixed
	 */
	public function update_expiry( $p_number_of_hours ) {

		global $wpdb;

		// Table name with WordPress prefix.
		//
		$_table_name = \EB\CSAS\DB\get_carts_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}custom_csas_data SET expired = 1 WHERE `updated_at` + INTERVAL %d HOUR <= NOW() AND expired = 0;",
				$p_number_of_hours
			)
		);
	}
}
