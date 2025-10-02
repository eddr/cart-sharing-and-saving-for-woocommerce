<?php
/**
 * Namespace for basic DB table infrastructure
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\DB;

global $wpdb;

$_custom_db_version = '1.0.55';

add_action( 'init', __NAMESPACE__ . '\create_custom_csas_table' );
/**
 * Get CSAS carts table name.
 *
 * @return string.
 */
function get_carts_table_name() {

	global $wpdb;
	$_table_name = $wpdb->prefix . 'custom_csas_data';

	return $_table_name;
}
/**
 * Get CSAS carts meta table name.
 *
 * @return string.
 */
function get_carts_meta_table_name() {

	global $wpdb;
	$_table_name = $wpdb->prefix . 'custom_csas_meta';

	return $_table_name;
}
/**
 * Create custom CSAS tables if needed.
 *
 * @return void.
 */
function create_custom_csas_table() {

	global $_custom_db_version;
	$_current_db_version = get_option( 'csas_db_version', 0 );

	if ( $_current_db_version < $_custom_db_version ) {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		global $charset_collate;

		$_main_table_name = get_carts_table_name();
		$_meta_table_name = get_carts_meta_table_name();

		// Main table.
		// .
		$_main_table_sql = "CREATE TABLE $_main_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			csas_hash varchar(60) NOT NULL,
			wc_cart_hash char(32),
			wc_session_id char(32),
			type varchar(1) NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			object_id bigint(20) NOT NULL,
			product_ids varchar(512),
			items longtext,
			coupons text,
			totals text,
			expired boolean DEFAULT 0,
			PRIMARY KEY (id),
			KEY csas_hash_wc_cart_hash (csas_hash,wc_cart_hash,product_ids),
			KEY wc_cart_hash_object_id (wc_cart_hash,object_id,product_ids),
			KEY product_ids_object_id ( product_ids,object_id ),
			KEY expired_updated_at ( expired, updated_at ),
			UNIQUE KEY wc_session_csas_hash (wc_session_id,csas_hash),
			UNIQUE KEY csas_hash_wc_session (csas_hash,wc_session_id),
			UNIQUE KEY wc_cart_hash_wc_session_id_object_id_csas_hash (wc_cart_hash, wc_session_id, object_id, csas_hash )
		) $charset_collate;";

		// meta table.
		// .
		$_meta_table_sql = "CREATE TABLE $_meta_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			cart_id bigint(20) NOT NULL,
			meta_key varchar(255) NULL,
			meta_value longtext NULL,
			PRIMARY KEY (id),
			KEY cart_id (cart_id,meta_key),
			KEY meta_key (meta_key)
		) $charset_collate;";

		$_sql = $_main_table_sql . ' ' . $_meta_table_sql;

		dbDelta( $_sql );

		update_option( 'csas_db_version', $_custom_db_version );
	}
}
