<?php
/**
 * Saved Carts Products List Table.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\Admin\ListTables;

if ( ! class_exists( 'WP_List_Table' ) ) {

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table for products appearing in saved carts.
 */
/**
 * Admin list table for products appearing in saved carts.
 */
class Saved_Carts_Products_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$_construction_args = array(
			'singular' => __( 'Saved Products\' Cart', 'cart-sharing-and-saving-for-woocommerce' ),
			'plural'   => __( 'Saved Products\' Carts', 'cart-sharing-and-saving-for-woocommerce' ),
			'ajax'     => false,
		);
		parent::__construct( $_construction_args );
	}

	/**
	 * Get list table columns.
	 *
	 * @return array.
	 */
	public function get_columns() {

		$_columns = array(
			'product'    => __( 'Product', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_count' => __( 'Cart#', 'cart-sharing-and-saving-for-woocommerce' ),
		);
		return $_columns;
	}

	/**
	 * Render checkbox column.
	 *
	 * @param array $p_item Row item.
	 * @return string.
	 */
	protected function column_cb( $p_item ) {

		return sprintf( '<input type="checkbox" name="cart[]" value="%s" />', $p_item->id );
	}

	/**

	 * Render product column.
	 *
	 * @param array $p_item Row item.
	 * @return string.
	 */
	public function column_product( $p_item ) {
		return sprintf( '<strong>%s</strong>', $p_item->product_title );
	}

	/**

	 * Render cart count column.
	 *
	 * @param array $p_item Row item.
	 * @return string|int.
	 */
	protected function column_cart_count( $p_item ) {

		return $p_item->cart_count;
	}

	/**

	 * Render products column.
	 *
	 * @param array $p_item Row item.
	 * @return string.
	 */
	protected function column_products( $p_item ) {

		$_product_ids_str = '';
		$_product_ids     = array_keys( $p_item->product_ids );

		foreach ( $_product_ids as $_pid ) {

			$_title            = get_the_title( $_pid );
			$_url              = get_edit_post_link( $_pid );
			$_product_ids_str .= '<a class="csas-admin-list-product" href="' . $_url . '">' . $_title . '</a>,';
		}
		return rtrim( $_product_ids_str, ',' );
	}


	/**
	 * Get bulk actions.
	 *
	 * @return array.
	 */
	public function get_bulk_actions() {

		$_actions = array();
		return $_actions;
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array.
	 */
	protected function get_sortable_columns() {

		$_sortable_columns = array();

		$_sortable_columns = array(
			'cart_count' => array( 'cart_count', false ),
		);

		return $_sortable_columns;
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void.
	 */
	public function prepare_items() {
		// Read-only filters from request; sanitize. Nonce verification is not required for GET filters.
		$product_ids    = isset( $product_ids ) ? array_map( 'absint', (array) wp_unslash( $product_ids ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$customer_users = isset( $customer_users ) ? array_map( 'absint', (array) wp_unslash( $customer_users ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby        = isset( $orderby ) ? sanitize_text_field( wp_unslash( $orderby ) ) : 'cart_count'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order          = isset( $order ) ? sanitize_text_field( wp_unslash( $order ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		global $wpdb;

		$_table_name       = \EB\CSAS\DB\get_carts_table_name();
		$_meta_table_name  = \EB\CSAS\DB\get_carts_meta_table_name();
		$_posts_table_name = $wpdb->prefix . 'posts';
		$_per_page         = 20;

		$_columns              = $this->get_columns();
		$_hidden               = array();
		$_sortable             = $this->get_sortable_columns();
		$_primary              = 'id';
		$this->_column_headers = array( $_columns, $_hidden, $_sortable, $_primary );

		$_current_page = $this->get_pagenum();

		if ( isset( $product_ids )
			|| isset( $customer_users ) ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );
		}

		// Search filters.
		$_search_products = $product_ids ?? array();
		$_search_users    = isset( $customer_users ) ? sanitize_text_field( implode( ',', $customer_users ) ) : '';

		$_where = "1=1
                AND cd.id = cmd.cart_id
                AND cmd.meta_key = 'pid'
                AND p.ID = cmd.meta_value";

		// Filter by product ID (Search term will be the product ID passed by WooCommerce's autocomplete).
		if ( ! empty( $_search_products ) ) {

			$_product_ids_str = '';
			foreach ( $_search_products as $_pid ) {

				$_product_ids_str .= $_pid . ',';
			}

			$_product_ids_str = rtrim( $_product_ids_str, ',' );

			$_where .= $wpdb->prepare( ' AND ( cmd.meta_value IN (%s) )', $_product_ids_str );

		}

		// Filter by username or email.
		if ( ! empty( $_search_users ) ) {

			$_where .= $wpdb->prepare( ' AND cd.object_id IN (%s)', $_search_users );
		}

		$_sql         = "SELECT count(cd.id) as cart_count
                FROM $_table_name cd
                , $_meta_table_name cmd
                , $_posts_table_name p
                WHERE $_where
                GROUP BY p.post_title, cmd.meta_value";
		$_total_items = $wpdb->get_var( $_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$_pagination_args = array(
			'total_items' => $_total_items,
			'per_page'    => $_per_page,
			'total_pages' => ceil( $_total_items / $_per_page ),
		);
		$this->set_pagination_args( $_pagination_args );

		$_orderby = ! empty( $orderby ) ? sanitize_text_field( $orderby ) : 'cart_count';
		$_order   = ! empty( $order ) ? sanitize_text_field( $order ) : 'desc';

		$_offset = ( $_current_page - 1 ) * $_per_page;
		$_sql    = "SELECT p.post_title as product_title, cmd.meta_value as pid, count(cd.id) as cart_count
			FROM $_table_name cd
			, $_meta_table_name cmd
			, $_posts_table_name p
			WHERE $_where
			GROUP BY p.post_title, cmd.meta_value
			ORDER BY $_orderby $_order 
			LIMIT $_per_page OFFSET $_offset";

		$this->items = $wpdb->get_results( $_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
