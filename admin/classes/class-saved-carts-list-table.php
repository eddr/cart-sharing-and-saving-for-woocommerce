<?php
/**
 * Admin View Carts Page.
 *
 * @package EB\CSAS
 */

namespace EB\CSAS\Admin\ListTables;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for Saved Carts in admin.
 */
class Saved_Carts_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$_construction_args = array(
			'singular' => __( 'Saved Cart', 'cart-sharing-and-saving-for-woocommerce' ),
			'plural'   => __( 'Saved Carts', 'cart-sharing-and-saving-for-woocommerce' ),
			'ajax'     => false,
		);
		parent::__construct( $_construction_args );
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$_columns = array(
			'cb'         => '<input type="checkbox" />',
			'id'         => __( 'Cart ID', 'cart-sharing-and-saving-for-woocommerce' ),
			'object_id'  => __( 'User ID', 'cart-sharing-and-saving-for-woocommerce' ),
			'products'   => __( 'Products', 'cart-sharing-and-saving-for-woocommerce' ),
			'updated_at' => __( 'Saved On', 'cart-sharing-and-saving-for-woocommerce' ),
			'expired'    => __( 'Expired', 'cart-sharing-and-saving-for-woocommerce' ),
		);
		return $_columns;
	}

	/**
	 * Render checkbox column.
	 *
	 * @param object $p_item Row item.
	 * @return string
	 */
	protected function column_cb( $p_item ) {

		return sprintf( '<input type="checkbox" name="cart[]" value="%s" />', $p_item->id );
	}

	/**
	 * Render cart ID column.
	 *
	 * @param object $p_item Row item.
	 * @return string
	 */
	public function column_id( $p_item ) {

		return sprintf( '<strong>%s</strong>', $p_item->csas_hash );
	}

	/**
	 * Render user/object ID column.
	 *
	 * @param object $p_item Row item.
	 * @return string
	 */
	protected function column_object_id( $p_item ) {

		return $p_item->object_id;
	}

	/**
	 * Render products column.
	 *
	 * @param object $p_item Row item.
	 * @return string
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
	 * Render updated at column.
	 *
	 * @param object $p_item Row item.
	 * @return string
	 */
	protected function column_updated_at( $p_item ) {

		return $p_item->updated_at;
	}

	/**
	 * Render expired column.
	 *
	 * @param object $p_item Row item.
	 * @return string
	 */
	protected function column_expired( $p_item ) {

		return ( 1 === (int) $p_item->expired ) ? __( 'yes', 'cart-sharing-and-saving-for-woocommerce' ) : __( 'no', 'cart-sharing-and-saving-for-woocommerce' );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$_actions = array(
			'delete' => __( 'Delete', 'cart-sharing-and-saving-for-woocommerce' ),
		);
		return $_actions;
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {

		$_sortable_columns = array(
			'id'         => array( 'id', false ),
			'object_id'  => array( 'object_id', false ),
			'updated_at' => array( 'updated_at', true ), // Make saved_on sortable.
		);
		return $_sortable_columns;
	}

	/**
	 * Prepare list-table items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {

		global $wpdb;

		$_table_name      = \EB\CSAS\DB\get_carts_table_name();
		$_meta_table_name = \EB\CSAS\DB\get_carts_meta_table_name(); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase -- namespace call preserved.

		$_per_page = 20;

		$_columns              = $this->get_columns();
		$_hidden               = array();
		$_sortable             = $this->get_sortable_columns();
		$_primary              = 'id';
		$this->_column_headers = array( $_columns, $_hidden, $_sortable, $_primary );

		$_current_page = $this->get_pagenum();

		// Search filters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['product_ids'] ) ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_raw_product_ids = wp_unslash( $_GET['product_ids'] );
			if ( is_array( $_raw_product_ids ) ) {

				$_search_products = array_filter( array_map( 'intval', $_raw_product_ids ) );
			} elseif ( is_string( $_raw_product_ids ) && '' !== $_raw_product_ids ) {

				$_search_products = array_filter( array_map( 'intval', explode( ',', $_raw_product_ids ) ) );
			} else {
				$_search_products = array();
			}
		} else {
			$_search_products = array();
		}

		$_search_users = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['customer_users'] ) ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_raw_users    = wp_unslash( $_GET['customer_users'] );
			$_users_array  = is_array( $_raw_users ) ? $_raw_users : explode( ',', (string) $_raw_users );
			$_users_array  = array_filter( array_map( 'sanitize_text_field', $_users_array ) );
			$_search_users = implode( ',', $_users_array );
		}

		$_search_cart_id = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['s_id'] ) ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );
			$_search_cart_id = sanitize_text_field( wp_unslash( $_GET['s_id'] ) );
		}

		$_where = 'WHERE 1=1';

		// Filter by product ID (search term is WC product ID from autocomplete).
		if ( ! empty( $_search_products ) ) {

			$_product_like_parts = array();
			$_params             = array();
			$_wheres             = array();

			foreach ( $_search_products as $_pid ) {
				// Build three LIKE variants exactly as before, but with % outside esc_like().
				$_base1 = $_pid . ':%';        // "... 12:% ...".
				$_base2 = ':' . $_pid . ':%';  // "... :12:% ...".
				$_base3 = ':' . $_pid;         // "... :12 ...".

				$_product_like_parts[] = '( product_ids LIKE %s OR product_ids LIKE %s OR product_ids LIKE %s )';

				$_params[] = '%' . $wpdb->esc_like( $_base1 ) . '%';
				$_params[] = '%' . $wpdb->esc_like( $_base2 ) . '%';
				$_params[] = '%' . $wpdb->esc_like( $_base3 ) . '%';
			}

			$_wheres[] = '( ' . implode( ' OR ', $_product_like_parts ) . ' )';

			// Preserve original logic (if you later append to $_where, keep that behavior).
		}

		// Filter by username or email.
		if ( ! empty( $_search_users ) ) {

			$_where .= $wpdb->prepare( ' AND object_id IN (%s)', $_search_users );
		}

		// Filter by Cart ID.
		if ( ! empty( $_search_cart_id ) ) {

			$_where .= $wpdb->prepare( ' AND csas_hash LIKE %s', '%' . $wpdb->esc_like( $_search_cart_id ) . '%' );
		}

		// Count items. Keep identifiers interpolated; PHPCS ignored accordingly.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$_total_items = (int) $wpdb->get_var( "SELECT COUNT(1) FROM $_table_name $_where" );

		$_pagination_args = array(
			'total_items' => $_total_items,
			'per_page'    => $_per_page,
			'total_pages' => ( $_per_page > 0 ) ? (int) ceil( $_total_items / $_per_page ) : 0,
		);
		$this->set_pagination_args( $_pagination_args );

		// Ordering from request.
		$_orderby = 'updated_at';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_REQUEST['orderby'] ) ) {
			$_orderby = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
		}

		$_order = 'desc';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_REQUEST['order'] ) ) {
			$_order_raw = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
			$_order     = ( 'ASC' === strtoupper( $_order_raw ) ) ? 'ASC' : 'DESC';
		}

		// Fetch items. Keep identifiers interpolated; PHPCS ignored accordingly.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $_table_name as csas_d $_where ORDER BY $_orderby $_order LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				$_per_page,
				( $_current_page - 1 ) * $_per_page
			)
		);

		// Populate denormalized product_ids for rendering.
		foreach ( $this->items as &$_row ) {

			$_row->product_ids = $this->get_row_items( $_row )['product_ids'];
		}
	}

	/**
	 * Extract product info for a row.
	 *
	 * @param object $p_row_data Row data object.
	 * @return array
	 */
	protected function get_row_items( $p_row_data ) {

		$_items = maybe_unserialize( $p_row_data->items );

		$_cart_products                = array();
		$_cart_products['product_ids'] = array();

		if ( ! empty( $_items ) ) {

			foreach ( $_items as $_item_key => $_item ) {

				$_pid                                   = ( ! empty( $_item['variation_id'] ) ) ? $_item['variation_id'] : $_item['product_id'];
				$_cart_products['product_ids'][ $_pid ] = 1;

				if ( ! isset( $_cart_products[ $_pid ] ) ) {

					$_cart_products[ $_pid ] = 0;
				}

				$_cart_products['product_ids'][ $_pid ] += $_item['quantity'];
			}
		}

		return $_cart_products;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			global $wpdb;
			$_table_name = \EB\CSAS\DB\get_carts_table_name();

			// Nonce verification call (does not alter flow).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['_wpnonce'] ) ) {

				$_nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
				wp_verify_nonce( $_nonce, 'bulk-' . $this->_args['plural'] );
			}

			// Unslash and sanitize incoming cart IDs.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_cart_ids = isset( $_POST['cart'] ) ? (array) wp_unslash( $_POST['cart'] ) : array();
			$_cart_ids = array_filter( array_map( 'intval', $_cart_ids ) );

			if ( ! empty( $_cart_ids ) ) {

				// Build dynamic placeholders for IDs; query structure preserved.
				$_placeholders = implode( ',', array_fill( 0, count( $_cart_ids ), '%d' ) );
				$_sql          = "DELETE FROM $_table_name WHERE id IN (" . $_placeholders . ')'; // =

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( $_sql, $_cart_ids ) );
			}
		}
	}
}
