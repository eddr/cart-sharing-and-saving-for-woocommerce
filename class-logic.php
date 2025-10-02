<?php
/**
 * The main class for all CSAS Logic.
 *
 * @package EBCSAS
 */

namespace EB\CSAS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use EB\CSAS\Options;

/**
 * CSAS Logic. Handles logic for saving, sharing, deleting, and applying saved carts.
 */
class Logic {

	/**
	 * Singleton instance.
	 *
	 * @var mixed The class instance.
	 */
	private static $_instance = null; // phpcs:ignore

	/**
	 * The queries object.
	 *
	 * @var object The queries object.
	 */
	private $_queries; // phpcs:ignore

	/**
	 * The hash class object.
	 *
	 * @var object The hash class object.
	 */
	private $_hash; // phpcs:ignore

	/**
	 * Class constructor. Creates one instance only.
	 */
	public function __construct() {

		if ( empty( $_instance ) ) {

			$this->init_hooks();
			self::$_instance = $this;
			$this->_queries  = new \EB\CSAS\Queries();
			$this->_hash     = new \EB\CSAS\Hash( $this->_queries );
		}
	}

	/**
	 * Initializes hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {

		//
		// hook cart ops.
		//
		add_action( 'wp_ajax_csas_cart_ops', array( $this, 'handle_csas_cart_ops_ajax' ) );
		add_action( 'wp_ajax_nopriv_csas_cart_ops', array( $this, 'handle_csas_cart_ops_ajax' ) );

		//
		// Handling logging in/out.
		//
		add_action( 'wp_login', array( $this, 'upon_logging_in' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'user_logout_action' ) );

		//
		// More.
		//
		add_action( 'csas_row_expired', array( $this, 'handle_expired_row' ) );
		add_filter( 'is_woocommerce', array( $this, 'add_custom_pages' ), 20 );
		add_action( 'csas_current_user_carts_changed', array( $this, 'update_csas_cookie' ) );
	}

	/**
	 * A getter for the stored CSAS hash class
	 *
	 * @return object The hash object.
	 */
	public function get_hash_ob() {

		return $this->_hash;
	}

	/**
	 * Gets the cart data for current session.
	 *
	 * @param [type] $p_session_id The WC session ID. If not set, uses current sessions ID.
	 * @return mixed Data if exists. Empty array if not.
	 */
	public function get_cart_data_for_wc_session( $p_session_id = null ) {

		if ( empty( $p_session_id ) ) {

			$p_session_id = self::get_woocommerce_session_id();
		}
		$_data = $this->_queries->get_cart_data_for_wc_session( $p_session_id );

		return $_data ?? array();
	}

	/**
	 * Get WooCommerce "customer id" from the session cookie without touching WC()->session.
	 *
	 * Works for guests and logged-in users. Returns '' if no cookie is present.
	 *
	 * @return string Customer ID string (not the WP user ID), or '' if unavailable.
	 */
	public static function get_wc_customer_id_from_cookie() {
		// Name of the Woo session cookie.
		$_cookie_name = 'wp_woocommerce_session_' . COOKIEHASH;

		if ( empty( $_COOKIE[ $_cookie_name ] ) ) {
			return '';
		}

		$_raw  = (string) sanitize_text_field( wp_unslash( $_COOKIE[ $_cookie_name ] ) );
		$_raw  = sanitize_text_field( $_raw ); // basic hardening.
		$_bits = explode( '|', $_raw );

		// First token is the customer id.
		$_customer_id = isset( $_bits[0] ) ? sanitize_text_field( $_bits[0] ) : '';

		return $_customer_id;
	}

	/**
	 * Updates user cart cookie.
	 *
	 * @param string $p_csas_hash CSAS cart hash.
	 * @return void
	 */
	public static function update_csas_cookie( $p_csas_hash ) {

		$_cookie_name                = 'csas_hash';
		$_wc_session_expiration_time = apply_filters( 'wc_session_expiration', MONTH_IN_SECONDS );

		setcookie( $_cookie_name, $p_csas_hash, array( 'expires' => $_wc_session_expiration_time ) );
	}

	/**
	 * Updates user cart cookie.
	 *
	 * @param string $p_csas_hash CSAS cart hash.
	 * @return void
	 */
	public function update_user_cart_cookie( $p_csas_hash ) {

		$p_user_id = get_current_user_id();
		self::update_csas_cookie( $p_csas_hash );
	}
	/**
	 * Returns current WC session ID. Uses WooCommerce session if available, otherwise tries to get it from the cookie.
	 *
	 * @return string The WC customer ID.
	 */
	public static function get_woocommerce_session_id() {

		if ( is_null( WC()->session ) ) {

			$_wc_customer_id = self::get_wc_customer_id_from_cookie();
		} else {

			$_wc_customer_id = WC()->session->get_customer_id();

		}

		return $_wc_customer_id;
	}

	/**
	 * Returns the carts# limit for current user ( guest or logged in).
	 *
	 * @return int limit of carts user can save.
	 */
	public function get_current_user_carts_limit() {

		global $_csas_options;

		$_limit = is_user_logged_in() ?
				$_csas_options->get_user_cart_limit() :
				$_csas_options->get_guest_cart_limit();

		return $_limit;
	}
	/**
	 * Returns the current user ( guest or logged in ) saved carts number
	 *
	 * @return int number of saved carts for current user.
	 */
	public function get_current_user_carts_num() {

		$_carts_num = count( $this->get_current_carts_data() );

		return $_carts_num;
	}
	/**
	 * Returns true if carts number limit reached. False otherwise
	 *
	 * @return bool true if cart limit reached, false otherwise.
	 */
	public function get_saved_carts_limit_reached() {

		$_carts_num     = $this->get_current_user_carts_num();
		$_current_limit = $this->get_current_user_carts_limit();

		return $_carts_num >= $_current_limit;
	}

	/**
	 * Returns the "limit reached" message for current user ( guest or logged in )
	 *
	 * @return string the message text.
	 */
	public function get_current_user_limit_reached_message() {

		global $_csas_options;

		$_msg = is_user_logged_in() ?
				$_csas_options->get_guest_cart_limit_reached_message( true ) :
				$_csas_options->get_user_cart_limit_reached_message( true );

		return $_msg;
	}

	/**
	 * Returns saved cart data for the given CSAS hash.
	 *
	 * @param [type]  $p_csas_hash The hash by which the cart is IDed.
	 * @param boolean $p_allow_expired true for receiving expired carts as well.
	 * @return array The CSAS cart data.
	 */
	public function get_cart_saved_for_csas_hash( $p_csas_hash, $p_allow_expired = true ) {

		$_cart_data = $this->_queries->get_cart_data_for_csas_hash( $p_csas_hash, $p_allow_expired );
		return $_cart_data;
	}

	/**
	 * Returns saved cart data for the given user ID. If non given or the ID = 0 ( guest ), uses session
	 *
	 * @param [type] $p_user_id WP User ID.
	 * @return array User's saved carts.
	 */
	public function get_cart_contents_for_user( $p_user_id = null ) {

		$_carts = $p_user_id ?
				( $this->_queries->get_cart_data_for_user( $p_user_id ) ?? array() ) :
				( $this->get_cart_data_for_wc_session() ?? array() );

		return $_carts;
	}

	/**
	 * Returns saved cart data for the current user ID.
	 * If the user is a guest, returns the cart data saved for this WC session.
	 *
	 * @param array $p_args Currently not in use.
	 * @return array The current user's ( logged-in or not ) carts.
	 */
	public function get_current_carts_data( $p_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$_user_id = get_current_user_id();

		$_cache_key = \EB\CSAS\Cache\get_cache_key_for_current_user();

		$_cached = wp_cache_get( $_cache_key, 'csas' );
		if ( false !== $_cached ) {
			return $_cached;
		}

		$_carts = $this->get_cart_contents_for_user( $_user_id );

		$_wc_session_expiration_time = apply_filters( 'wc_session_expiration', MONTH_IN_SECONDS );
		wp_cache_set( $_cache_key, $_carts, 'csas', $_wc_session_expiration_time ); // Cache for 1 hour.

		return $_carts;
	}

	/**
	 * Returns the CSAS hash for the current cart, if exists.
	 *
	 * @return string The CSAS hash. Empty if no cart saved for current user/session.
	 */
	public function get_current_cart_csas_hash() {

		$_user_id    = get_current_user_id();
		$_hash       = WC()->cart->get_cart_hash();
		$_session_id = self::get_woocommerce_session_id();

		$_csas_hash = $this->_queries->get_csas_hash( $_user_id, $_hash, $_session_id );

		return $_csas_hash;
	}

	/**
	 * Saves current cart data for current user, either guest or logged in.
	 *
	 * @return mixed The CSAS unique hash. Null if no WC cart exists.
	 */
	public function save_cart_for_user() {

		$_user_id          = get_current_user_id();
		$_hash             = WC()->cart->get_cart_hash();
		$_unique_csas_hash = null;

		// There are items in the cart.
		//
		if ( ! empty( $_hash ) ) {

			$_session_id       = self::get_woocommerce_session_id();
			$_unique_csas_hash = $this->_queries->get_csas_hash( $_user_id, $_hash, $_session_id );

			if ( empty( $_unique_csas_hash ) ) {

				$_unique_csas_hash = $this->_hash->get_unique_cart_hash();
			}

			$_items        = WC()->cart->get_cart_for_session();
			$_coupons      = WC()->cart->get_applied_coupons();
			$_session_data = array(
				'items'   => $_items,
				'coupons' => $_coupons,
			);

			$this->add_cart_to_user(
				$_items,
				$_coupons,
				WC()->cart->get_totals(),
				$_session_data,
				$_unique_csas_hash,
				$_hash,
				$_session_id,
				! empty( $_user_id ) ? $_user_id : null,
				! empty( $_user_id ) ? 1 : 0
			);
		}

		return $_unique_csas_hash;
	}

	/**
	 * Adds the current WC cart to the user's saved carts.
	 *
	 * @param array   $p_items Cart's items.
	 * @param array   $p_coupons Cart's coupons.
	 * @param array   $p_totals Cart's totals.
	 * @param array   $p_session_data WC session data.
	 * @param string  $p_unique_csas_hash CSAS hash.
	 * @param string  $p_hash WC cart hash.
	 * @param string  $p_session_id WC session ID.
	 * @param integer $p_user_id WP User ID.
	 * @param integer $p_type Cart's type. 1 for logged in user, 0 otherwise.
	 * @param array   $p_args additional arguments. Currently not in use.
	 * @return void
	 */
	public function add_cart_to_user( $p_items, $p_coupons, $p_totals, $p_session_data, $p_unique_csas_hash, $p_hash, $p_session_id, $p_user_id = null, $p_type = 0, $p_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$_product_ids = $this->extract_cart_product_ids( $p_session_data['items'] );

		// add the product's title.
		//
		foreach ( $p_items as &$item ) {
			$_pid          = empty( $item['variation_id'] ) ?
					$item['product_id'] : $item['variation_id'];
			$_title        = get_the_title( $_pid );
			$item['title'] = $_title;
		}
		$_updated_at = gmdate( 'Y-m-d H:i:s' );

		$this->_queries->insert_csas_data(
			$p_unique_csas_hash,
			$p_hash,
			$p_session_id,
			$_product_ids,
			$_updated_at,
			$p_items,
			$p_coupons,
			$p_totals,
			$p_type,
			$p_user_id
		);
	}

	/**
	 * Extract products' IDs from cart items array.
	 *
	 * @param array $p_cart_items cart items array.
	 * @return array products IDs and quantities.
	 */
	private function extract_cart_product_ids( $p_cart_items ) {

		$_product_ids = array();

		foreach ( $p_cart_items as $item ) {
			$_vid = $item['variation_id'] ?? 0;
			$_pid = $item['product_id'];

			$_product_ids[] = array(
				'pid' => $_pid,
				'vid' => $_vid,
				'qty' => $item['quantity'],
			);
		}

		return $_product_ids;
	}

	/**
	 * Build the public share URL for the current cart.
	 *
	 * @return string Share URL.
	 */
	public static function get_share_url_for_current_cart() {

		$_share_url = self::get_share_url_by_hash( self::get_current_cart_csas_hash() );

		return $_share_url;
	}

	/**
	 * Build the public share URL for a given cart hash.
	 *
	 * @param string $p_csas_hash Cart identifier hash.
	 * @return string Share URL.
	 */
	public static function get_share_url_by_hash( $p_csas_hash ) {

		global $_csas_options;

		$_page_id   = $_csas_options->get_share_cart_page_id();
		$_page_name = get_post_field( 'post_name', $_page_id );

		$_share_url = get_site_url() . '/' . $_page_name . '/' . $p_csas_hash;

		return $_share_url;
	}
	/**
	 * Executes CSAS operation, one of the following: load,save,delete,share.
	 *
	 * @param string $p_csas_cart_op The cart operation type.
	 * @param string $p_csas_hash The cart hash.
	 * @param bool   $p_revalidate Should revlidate csas cart hash.
	 * @return array The operation result, including 'msg' and 'success'.
	 */
	public function execute_op( $p_csas_cart_op, $p_csas_hash, $p_revalidate = false ) {

		global $_csas_options;
		$_csas_hash = $p_csas_hash;

		$_return_data = array(
			'success' => true,
			'msg'     => __( 'Operation not supported!', 'cart-sharing-and-saving-for-woocommerce' ),
		);

		$_lang = \EB\CSAS\Lang::get_current_lang();

		if ( 'delete_cart' === $p_csas_cart_op ) {
			$_carts = $this->get_current_carts_data();

			if ( ! empty( $_carts ) ) {
				$_msg = $_csas_options->get_cart_deleted_message();

				$_res = $this->_queries->delete_csas_data_by_csas_cart_hash( $_csas_hash );
				do_action( 'csas_current_user_carts_changed', $_csas_hash );

				$_return_data = array(
					'success' => true,
					'msg'     => $_msg,
					'data'    => array( 'cart_hash' => $_csas_hash ),
				);
			}
			// Cart saving operation.
			//
		} elseif (
			$_csas_options->get_allow_cart_saving()
			&& ( 'save_cart' === $p_csas_cart_op )
		) {

			$_success = false;

			if ( ! is_user_logged_in()
				&& ! $_csas_options->get_allow_guests_ops() ) {

				$_success       = true;
				$_msg           = $_csas_options->get_guest_not_allowed_to_save_message();
				$_limit_reached = true;

			} else {

				$_limit_reached = $this->get_saved_carts_limit_reached();
				$_success       = $_limit_reached ? false : true;
				$_msg           = $_limit_reached ?
					$this->get_current_user_limit_reached_message() :
					$_csas_options->get_cart_saved_message( true );

				if ( ! $_limit_reached ) {

					$_saved_carts_page_url = $_csas_options->get_saved_carts_url();
					$_msg                  = str_replace( '{saved_carts_url}', $_saved_carts_page_url, $_msg );

					$_csas_hash = $this->save_cart_for_user();  // Save the cart first.

					if ( $_success ) {

						do_action( 'csas_current_user_carts_changed', $_csas_hash );
					}
				}
			}

			$_return_data = array(
				'success'       => $_success,
				'msg'           => $_msg,
				'data'          => array( 'cart_hash' => $_csas_hash ),
				'limit_reached' => $_limit_reached,
			);
		} elseif (
			$_csas_options->get_allow_cart_sharing()
				&& ( 'share_cart' === $p_csas_cart_op )
		) {
			if ( empty( $_csas_hash ) || $p_revalidate ) {

				$_existing_csas_hash = self::get_current_cart_csas_hash();

				// Anyway, save in order to update timestamps.
				//
				$_csas_hash = $this->save_cart_for_user();  // Save the cart first.
				do_action( 'csas_current_user_carts_changed', $_csas_hash );
			}

			$_page_id             = $_csas_options->get_share_cart_page_id();
			$_page_name           = get_post_field( 'post_name', $_page_id );
			$_msg                 = $_csas_options->get_cart_shared_message();
			$_shared_cart_message = $_msg;

			// Generate a shareable link (customize this URL to where you want the shared cart to be handled).
			//
			$_share_url        = get_site_url() . '/' . $_lang . '/' . $_page_name . '/' . $_csas_hash;
			$_clipboard_url    = plugins_url( '/assets/images/clipboard.png', __FILE__ );
			$_clipboard_button = '<img class="csas-copy-link" src="' . $_clipboard_url . '" class="csas-copy-link"/>';

			$_shared_cart_message = str_replace(
				array( '{shared_cart_url}', '{copy_link_button}' ),
				array( $_share_url, $_clipboard_button ),
				$_shared_cart_message
			);

			$_return_data = array(
				'msg'              => $_shared_cart_message,
				'share_cart_url'   => $_share_url,
				'copy_link_button' => $_clipboard_button,
				'data'             => array( 'cart_hash' => $_csas_hash ),
			);
		} elseif ( 'load_cart' === $p_csas_cart_op ) {

			$_cart_data = $this->get_cart_saved_for_csas_hash( $_csas_hash );

			if ( ! empty( $_cart_data ) ) {

				$_items = maybe_unserialize( $_cart_data->items );

				if ( ! empty( $_items ) ) {

					$_coupons = maybe_unserialize( $_cart_data->coupons );
					$this->load_cart_data( $_items, $_coupons );

					$_msg = $_csas_options->get_cart_applied_message();
				}

				$_return_data = array( 'msg' => $_msg );
			}
		}

		return $_return_data;
	}
	/**
	 * Handles the CSAS Ajax requests.
	 *
	 * @return void
	 */
	public function handle_csas_cart_ops_ajax() {

		check_ajax_referer( 'csas_cart_ops', 'nonce' );

		$_csas_cart_op = sanitize_text_field( wp_unslash( $_POST['op'] ?? '' ) );
		$_csas_hash    = sanitize_text_field( wp_unslash( $_POST['csas_hash'] ?? '' ) );
		$_revalidate   = sanitize_text_field( wp_unslash( $_POST['revalidate'] ?? '' ) ) === '1';

		$_return_data = $this->execute_op( $_csas_cart_op, $_csas_hash, $_revalidate );

		wp_send_json( $_return_data );
		wp_die();
	}

	/**
	 * Clears current session Cart and loading given items and coupons.
	 *
	 * @param [type] $p_cart_items cart items to load.
	 * @param [type] $p_applied_coupons coupons to load.
	 * @return void
	 */
	public function load_cart_data( $p_cart_items, $p_applied_coupons ) {

		// Clear the cart first to prevent duplicate items.
		//
		WC()->cart->empty_cart();

		// Loop through the cart items and add them to the cart.
		//
		foreach ( $p_cart_items as $item ) {
			$_pid = ! empty( $item['variation_id'] ?? null ) ?
					$item['variation_id'] : $item['product_id'];
			WC()->cart->add_to_cart( $_pid, $item['quantity'] );
		}

		// Apply the coupon codes.
		//
		if ( ! empty( $p_applied_coupons ) ) {
			foreach ( $p_applied_coupons as $coupon_code ) {
				WC()->cart->apply_coupon( $coupon_code );
			}
		}
	}

	/**
	 * Runs when a user logs in.
	 *
	 * @param string   $p_user_login The user's username.
	 * @param \WP_User $p_user The WP User object.
	 * @return void
	 */
	public function upon_logging_in( $p_user_login, \WP_User $p_user ) {
	}
	/**
	 * Runs when a user logs out. Currently used to clear carts' session data.
	 *
	 * @return void
	 */
	public function user_logout_action() {

		$this->clear_session_data();
	}

	/**
	 * Gets current sessions data from WC.
	 *
	 * @return mixed WC session data.
	 */
	public function get_session_data() {

		$_session_handler = new WC_Session_Handler();
		$_session_data    = $_session_handler->get_session( $_session_handler->get_customer_unique_id() );

		return $_session_data;
	}
	/**
	 * Clears session data
	 *
	 * @return void
	 */
	public function clear_session_data() {

		$_data = get_session_data();
		unset( $_session_data['cart'] );
		unset( $_session_data['applied_coupons'] );
	}

	/**
	 * Rearranges the raw cart data into items, coupons and totals.
	 *
	 * @param array $p_carts_data raw cart data from WC.
	 * @return array The carts data.
	 */
	public function get_formatted_carts_data( $p_carts_data ) {

		$_formatted_carts_data = array();

		foreach ( $p_carts_data as $serialized_cart_contents ) {

			$_csas_hash = $serialized_cart_contents->csas_hash;
			$_items     = maybe_unserialize( $serialized_cart_contents->items ?? '' );

			if ( ! empty( $_items ) ) {
				$_formatted_carts_data[ $_csas_hash ] = array( 'items' => $_items );

				$_coupons = maybe_unserialize( $serialized_cart_contents->coupons );

				if ( ! empty( $_coupons ) ) {
					$_formatted_carts_data[ $_csas_hash ]['coupons'] = $_coupons;
				}
			}
		}

		return $_formatted_carts_data;
	}
	/**
	 * Checks if a timestamp + x hours has already past.
	 *
	 * @param mixed $p_timestamp Reference timestamp.
	 * @param int   $p_period_in_hours expiration period in hours.
	 * @return string Time left message.
	 */
	public function get_time_left( $p_timestamp, $p_period_in_hours ) {

		// Calculate the end time by adding the period to the initial timestamp.
		//
		$_end_time = intval( $p_timestamp ) + ( intval( $p_period_in_hours ) * HOUR_IN_SECONDS );

		// Get the current time according to WordPress settings (adjusted for timezone).
		//
		$_current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// Calculate the time left.
		//
		$_time_left = $_end_time - $_current_time;

		if ( $_time_left > 0 ) {
			// Convert the time left into a human-readable format.
			//
			$_time_left_human = \human_time_diff( $_current_time, $_end_time );
			return "Time left: $_time_left_human";
		} else {
			return 'The period has expired.';
		}
	}

	/**
	 * Finds and processes expired carts.
	 * Gets the expired rows, updates them with expire = 1 and processes them.
	 *
	 * @return void
	 */
	public function process_carts_expiration() {

		global $_csas_options;

		// Set the number of hours for expiration.
		//
		$_number_of_hours = $_csas_options->get_cart_expiration();
		if ( ! empty( $_number_of_hours ) ) {
			$_expired_rows = $this->_queries->get_newly_expired_rows( $_number_of_hours );

			// Update the rows to set 'expired' = 1.
			//
			$this->_queries->update_expiry( $_number_of_hours );

			// Process the expired rows by firing the action.
			//
			$this->process_expired_rows( $_expired_rows );
		}
	}

	/**
	 * Expired rows processing.
	 *
	 * @param array $p_rows expired carts DB rows.
	 * @return void
	 */
	public function process_expired_rows( $p_rows ) {

		if ( ! empty( $p_rows ) && is_array( $p_rows ) ) {
			foreach ( $p_rows as $row ) {
				/**
				 * Fires an action when a row expires.
				 *
				 * @param array $row The data of the expired row.
				 */
				do_action( 'csas_row_expired', $row );
			}
		}
	}

	/**
	 * Processes one expired row.
	 *
	 * @param [type] $row The cart/row data.
	 * @return void
	 */
	public function handle_expired_row( $row ) {
	}

	/**
	 * Adds CSAS pages as woocommerce pages in order for some functionalities to be applied.
	 *
	 * @param boolean $p_is_woocommerce true if a WC page.
	 * @return true if the page is WC admin page.
	 */
	public function add_custom_pages( $p_is_woocommerce ) {

		global $_csas_options;
		$_share_cart_page_id  = intval( $_csas_options->get_share_cart_page_id() );
		$_saved_carts_page_id = intval( $_csas_options->get_saved_carts_page_id() );
		$_additional_wc_pages = array( $_share_cart_page_id, $_saved_carts_page_id );

		$p_is_woocommerce = $p_is_woocommerce
							|| in_array( get_the_ID(), $_additional_wc_pages, true );

		return $p_is_woocommerce;
	}
}
