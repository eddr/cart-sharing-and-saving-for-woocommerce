<?php
/**
 * Auto-fixed header.
 *
 * @package EBCSAS
 */

/**
 * Plugin's options.
 *
 * @package EBCSAS
 */

namespace EB\CSAS;

/**
 * Plugin's options class.
 */
class Options {

	/**
	 * True if current language should be used for translation.
	 *
	 * @var boolean
	 */
	private $_use_current_lang = true; // phpcs:ignore

	/**
	 * Class singleton constructor.
	 *
	 * @param boolean $p_use_current_lang true if current language should be used for translation. False otherwise.
	 */
	public function __construct( $p_use_current_lang = true ) {

		$this->_use_current_lang = $p_use_current_lang;
	}

	/**
	 * Get current language.
	 *
	 * @return string The language code.
	 */
	public function get_lang() {

		$_lang = \EB\CSAS\Lang::get_current_lang();

		return $_lang;
	}
	/**
	 * Gets the plugin's settings page urln
	 *
	 * @return string The URL.
	 */
	public function get_settings_page_url() {

		$_url = admin_url( 'admin.php?page=ebcsas-options' );

		return $_url;
	}

	/**
	 * General public function to get option.
	 *
	 * @param string  $p_field Field's name.
	 * @param string  $p_default_value Default value in case no current value exists.
	 * @param boolean $p_use_lang true if language should be taken into account.
	 * @return mixed The value.
	 */
	public function get_option_value( $p_field = '', $p_default_value = '', $p_use_lang = null ) {

		$_options  = $this->get_options();
		$_use_lang = is_null( $p_use_lang ) ? $this->_use_current_lang : $p_use_lang;

		if ( ! $_use_lang ) {

			$_val = isset( $_options[ $p_field ] ) ? $_options[ $p_field ] : $p_default_value;
		} else {

			$_lang = $this->get_lang();

			$_val = ! empty( $_options[ $_lang ][ $p_field ] ?? '' ) ? $_options[ $_lang ][ $p_field ] : $p_default_value;
		}
		return $_val;
	}

	/**
	 * Gets all plugin's options
	 *
	 * @return array The options.
	 */
	public function get_options() {

		$_options_prefix = $this->get_options_prefix();
		$_options        = get_option( $_options_prefix );

		if ( ! is_array( $_options ) ) {
			$_options = array();
		}
		return $_options;
	}

	/**
	 * Sets plugin's option.
	 *
	 * @param string $p_option_name Option name.
	 * @param string $p_option_val Option value.
	 * @return mixed false if option name is empty.
	 */
	public function set_option( $p_option_name = '', $p_option_val = '' ) {

		if ( empty( $p_option_name ) ) {
			return false;
		}

		$_options_prefix                = $this->get_options_prefix();
		$_all_options                   = $this->get_options();
		$_all_options[ $p_option_name ] = $p_option_val;

		\update_option( $_options_prefix, $_all_options );
	}

	/**
	 * Gets the plugin's option meta prefix.
	 *
	 * @return string The prefix.
	 */
	public function get_options_prefix() {

		$_options_prefix = '_csas_admin';
		return $_options_prefix;
	}

	/**
	 * Gets all buttons' names.
	 *
	 * @return array buttons' names.
	 */
	public function get_available_button_names() {

		return array(
			'save'   => _x( 'Save', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
			'share'  => _x( 'Share', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
			'load'   => _x( 'Load', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
			'delete' => _x( 'Delete', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
		);
	}

	/**
	 * Plugin's enabled option.
	 *
	 * @return boolean true if enabled, false otherwise.
	 */
	public function get_is_enabled() {

		return $this->get_option_value( 'is_enabled', false, false ) === '1';
	}

	/**
	 * Allow cart saving option.
	 *
	 * @return boolean true if saving cart is allowed. false otherwise.
	 */
	public function get_allow_cart_saving() {

		return $this->get_option_value( 'allow_saving', '0', false ) === '1';
	}

	/**
	 * Allow cart sharing option.
	 *
	 * @return boolean true if sharing cart is allowed. false otherwise.
	 */
	public function get_allow_cart_sharing() {

		return $this->get_option_value( 'allow_sharing', '0', false ) === '1';
	}

	/**
	 * Allow guests to save and share option.
	 *
	 * @return boolean true if guests are allowed to save and share. false otherwise.
	 */
	public function get_allow_guests_ops() {

		return $this->get_option_value( 'allow_guests_ops', '0', false ) === '1';
	}

	/**
	 * Show applied coupon when displaying saved cart option.
	 *
	 * @return boolean true if coupon should be shown. false otherwise.
	 */
	public function get_share_applied_coupons() {

		return $this->get_option_value( 'share_coupons', '0', false ) === '1';
	}

	/**
	 * True if native WC styles for buttons should be used..
	 *
	 * @return boolean true if should be used. false otherwise.
	 */
	public function get_use_wc_style_for_buttons() {

		$_use = $this->get_option_value( 'use_wc_style_for_buttons', true, false );
		return $_use;
	}

	/**
	 * Returns a plugin's button text. Types: save, share, delete, load.
	 *
	 * @param string $p_button_type button type.
	 * @param string $p_default default text.
	 * @return string The button text.
	 */
	public function get_button_text( $p_button_type, $p_default = '' ) {

		$_text = $this->get_option_value( $p_button_type . '_button_text', $p_default );

		return empty( $_text ) ? $p_default : $_text;
	}

	/**
	 * Returns share button text.
	 *
	 * @return string The button text.
	 */
	public function get_share_button_text() {

		return $this->get_button_text( 'share', __( 'Share', 'cart-sharing-and-saving-for-woocommerce' ) );
	}

	/**
	 * Returns load button text.
	 *
	 * @return string The button text.
	 */
	public function get_load_button_text() {

		return $this->get_button_text( 'load', __( 'Load', 'cart-sharing-and-saving-for-woocommerce' ) );
	}

	/**
	 * Returns delete button text.
	 *
	 * @return string The button text.
	 */
	public function get_delete_button_text() {

		return $this->get_button_text( 'delete', __( 'Delete', 'cart-sharing-and-saving-for-woocommerce' ) );
	}

	/**
	 * Returns save button text.
	 *
	 * @return string The button text.
	 */
	public function get_save_button_text() {

		return $this->get_button_text( 'save', __( 'Add to My Carts', 'cart-sharing-and-saving-for-woocommerce' ) );
	}

	/**
	 * Returns saved carts ("My Carts") page URL.
	 *
	 * @return string The URL.
	 */
	public function get_saved_carts_url() {

		$_saved_carts_page_id = $this->get_saved_carts_page_id();
		$_translated_page_id  = null;

		$_page_id = $_translated_page_id ?? $_saved_carts_page_id;
		return empty( $_page_id ) ? '' : \get_the_permalink( $_page_id );
	}

	/**
	 * Button locations.
	 *
	 * @return array All locations.
	 */
	public function get_visibility_locations() {

		$_locations = array(
			'manual' => esc_html_x( 'Manual/None', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
			'bottom' => esc_html_x( 'Bottom', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
			'top'    => esc_html_x( 'Top', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
		);

		return $_locations;
	}

	/**
	 * Buttons location on cart page.
	 *
	 * @return string The location.
	 */
	public function get_cart_page_visibility() {

		return $this->get_option_value( 'cart_page_visibility', 'manual', false );
	}

	/**
	 * Buttons location on checkout page.
	 *
	 * @return string The location.
	 */
	public function get_checkout_page_visibility() {

		return $this->get_option_value( 'checkout_page_visibility', 'manual', false );
	}

	/**
	 * Buttons location on mini cart window.
	 *
	 * @return string The location.
	 */
	public function get_mini_cart_visibility() {

		return $this->get_option_value( 'mini_cart_visibility', 'manual', false );
	}

	/**
	 * Saved carts limit for guests.
	 *
	 * @return number The limit.
	 */
	public function get_guest_cart_limit() {

		return $this->get_option_value( 'guest_cart_limit', 1, false );
	}

	/**
	 * Saved carts limit for registered users.
	 *
	 * @return number The limit.
	 */
	public function get_user_cart_limit() {

		return $this->get_option_value( 'user_cart_limit', 3, false );
	}

	/**
	 * Dialog box titles for frontend notifications.
	 *
	 * @return array The titles.
	 */
	public function get_all_front_titles() {

		$_titles = array(
			'cart_applied_title'  => __( 'Applying cart...', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_sharing_title'  => __( 'Sharing cart...', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_saving_title'   => __( 'Saving cart...', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_deleting_title' => __( 'Deleting cart...', 'cart-sharing-and-saving-for-woocommerce' ),
		);

		return $_titles;
	}

	/**
	 * All plugin's text messages for frontend notifications.
	 *
	 * @return array The texts.
	 */
	public function get_all_text_messages_types() {

		$_types = array(
			'guest_cart_limit_reached',
			'user_cart_limit_reached',
			'guest_not_allowed_to_save',
			'cart_deleted',
			'cart_shared',
			'cart_applied',
			'cart_saved',
		);

		return $_types;
	}

	/**
	 * All plugin's default text messages for frontend notifications.
	 * At this point - translated.
	 *
	 * @param boolean $p_translate true if should be translated. false otherwise.
	 * @return array The texts.
	 */
	public function get_default_messages( $p_translate = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$_messages = array(

			'guest_cart_limit_reached'  => __( 'Maximum number of carts has been saved. Please check <a href="{saved_carts_url}">the saved carts page</a> and consider deleting some.', 'cart-sharing-and-saving-for-woocommerce' ),
			'user_cart_limit_reached'   => __( 'Maximum number of carts has been saved. Please check <a href="{saved_carts_url}">the saved carts page</a> and consider deleting some.', 'cart-sharing-and-saving-for-woocommerce' ),
			'guest_not_allowed_to_save' => __( 'Guests are not allowed to save carts. Please create an account or contact the site support.', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_saved'                => __( 'Cart saved successfully. Please check <a href="{saved_carts_url}">saved carts page</a> to see them.', 'cart-sharing-and-saving-for-woocommerce' ),
			'cart_deleted'              => __( 'Cart deleted successfully.', 'cart-sharing-and-saving-for-woocommerce' ),
			/* translators: %s: page ID */
			'cart_shared'               => sprintf( __( 'You can share your cart via %s{copy_link_button}', 'cart-sharing-and-saving-for-woocommerce' ), '<a class="shared-cart-link" target="_blank" href="{shared_cart_url}" />' . __( 'this link', 'cart-sharing-and-saving-for-woocommerce' ) . '</a>' ),
			'cart_applied'              => __( 'Cart applied successfully and the items will show on the cart page.', 'cart-sharing-and-saving-for-woocommerce' ),
		);

		return $_messages;
	}
	/**
	 * Default frontend text message.
	 *
	 * @param boolean $p_type The specific message type.
	 * @return string The text..
	 */
	public function get_default_message( $p_type ) {

		$_messages = $this->get_default_messages();

		return $_messages[ $p_type ];
	}

	/**
	 * Cart limit reached default message for guests.
	 *
	 * @return string The text.
	 */
	public function get_guest_cart_limit_reached_message_default() {

		$_default_message = $this->get_default_message( 'guest_cart_limit_reached' );

		return $_default_message;
	}
	/**
	 * Cart limit reached default message for registered users.
	 *
	 * @return string The text.
	 */
	public function get_user_cart_limit_reached_message_default() {

		$_default_message = $this->get_default_message( 'user_cart_limit_reached' );

		return $_default_message;
	}
	/**
	 * Guests not allowed to save default message for guests.
	 *
	 * @return string The text.
	 */
	public function get_guest_not_allowed_to_save_message_default() {

		$_default_message = $this->get_default_message( 'guest_not_allowed_to_save' );

		return $_default_message;
	}
	/**
	 * Cart limit reached message for guests.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @return string The text.
	 */
	public function get_guest_cart_limit_reached_message( $p_use_default = true ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'guest_cart_limit_reached' ) : null;

		$_message = $this->get_option_value( 'guest_cart_limit_reached_message', $_default_message );

		return $_message;
	}
	/**
	 * Cart limit reached message for registered users.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @return string The text.
	 */
	public function get_user_cart_limit_reached_message( $p_use_default = true ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'user_cart_limit_reached' ) : null;

		$_message = $this->get_option_value( 'user_cart_limit_reached_message', $_default_message );

		return $_message;
	}
	/**
	 * Guests not allowed to save for guests.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @return string The text.
	 */
	public function get_guest_not_allowed_to_save_message( $p_use_default = true ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'guest_not_allowed_to_save' ) : null;

		$_message = $this->get_option_value( 'guest_not_allowed_to_save_message', $_default_message );

		return $_message;
	}

	/**
	 * Cart Saved frontend notification.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @param boolean $p_use_lang true if language aware value should be returned.
	 * @return string The text.
	 */
	public function get_cart_saved_message( $p_use_default = true, $p_use_lang = null ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'cart_saved' ) : null;

		$_message = $this->get_option_value( 'cart_saved_message', $_default_message, $p_use_lang );

		return $_message;
	}
	/**
	 * Cart Shared frontend notification.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @param boolean $p_use_lang true if language aware value should be returned.
	 * @return string The text.
	 */
	public function get_cart_shared_message( $p_use_default = true, $p_use_lang = null ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'cart_shared' ) : null;

		$_message = $this->get_option_value( 'cart_shared_message', $_default_message, $p_use_lang );

		return $_message;
	}
	/**
	 * Cart Deleted frontend notification.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @param boolean $p_use_lang true if language aware value should be returned.
	 * @return string The text.
	 */
	public function get_cart_deleted_message( $p_use_default = true, $p_use_lang = null ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'cart_deleted' ) : null;

		$_message = $this->get_option_value( 'cart_deleted_message', $_default_message, $p_use_lang );

		return $_message;
	}
	/**
	 * Cart applied frontend notification.
	 *
	 * @param boolean $p_use_default true if default should be used. false otherwise.
	 * @param boolean $p_use_lang true if language aware value should be returned.
	 * @return string The text.
	 */
	public function get_cart_applied_message( $p_use_default = true, $p_use_lang = null ) {

		$_default_message = $p_use_default ?
							$this->get_default_message( 'cart_applied' ) : null;

		$_message = $this->get_option_value( 'cart_applied_message', $_default_message, $p_use_lang );

		return $_message;
	}
	/**
	 * All frontend notifications. Used as notifications after an operation.
	 *
	 * @param boolean $p_use_lang true if content should be language aware. false otherwise.
	 * @return array The texts.
	 */
	public function get_all_text_messages( $p_use_lang = false ) {

		$_types    = $this->get_all_text_messages_types();
		$_messages = array();

		foreach ( $_types as $type ) {
			$_msg               = call_user_func( array( $this, 'get_' . $type . '_message' ), true, $p_use_lang );
			$_messages[ $type ] = $_msg;
		}

		return $_messages;
	}

	/**
	 * Returns saved carts page ID.
	 *
	 * @return mixed The ID. Empty string if non exists.
	 */
	public function get_saved_carts_page_id() {

		return $this->get_option_value( 'saved_carts_page_id', '', true );
	}

	/**
	 * Returns share cart page ID.
	 *
	 * @return mixed The ID. Empty string if non exists.
	 */
	public function get_share_cart_page_id() {

		return $this->get_option_value( 'share_cart_page_id', '', true );
	}

	/**
	 * Get the value of a single option across all languages.
	 *
	 * @param string $p_key Option key to retrieve (e.g. 'option1').
	 * @param string $p_include_empty Should include non existent keys with value of null(?).
	 * @return array Map of language slug => value (null if not set).
	 */
	public function get_option_all_langs( $p_key, $p_include_empty = true ) {

		$_all = $this->get_options();
		$_out = array();

		foreach ( $_all as $_lang => $_opts ) {

			if ( isset( $_opts[ $p_key ] ) ) {

				$_out[ $_lang ] = $_opts[ $p_key ];
			} elseif ( $p_include_empty ) {

				$_out[ $_lang ] = null;
			}
		}

		return $_out;
	}

	/**
	 * Returns all share cart pages IDs.
	 * The reason for multiple IDs is localization.
	 *
	 * @return mixed The ID. Empty string if non exists.
	 */
	public function get_all_saved_carts_pages_ids() {

		$_page_ids = $this->get_option_all_langs( 'saved_carts_page_id', false );

		return $_page_ids;
	}

	/**
	 * Returns all share cart pages IDs.
	 * The reason for multiple IDs is localization.
	 *
	 * @return mixed The ID. Empty string if non exists.
	 */
	public function get_all_share_cart_pages_ids() {

		$_page_ids = $this->get_option_all_langs( 'share_cart_page_id', false );

		return $_page_ids;
	}

	/**
	 * Returns cart expiration, measured in hours.
	 *
	 * @return string Expiration period,
	 */
	public function get_cart_expiration() {

		return $this->get_option_value( 'cart_expiration', 0 );
	}
}

global $_csas_options;
$_csas_options = new Options();
