<?php
/**
 * Shortcodes file for CSAS plugin.
 *
 * @package CSAS
 */

namespace EB\CSAS\SHORTCODES;

use EB\CSAS\Options;

add_action( 'init', __NAMESPACE__ . '\create_shortcodes' );

/**
 * Registers all shortcodes used in the CSAS plugin.
 */
function create_shortcodes() {

	add_shortcode( 'csas_share_button', __NAMESPACE__ . '\share_button_func' );
	add_shortcode( 'csas_save_button', __NAMESPACE__ . '\save_button_func' );
	add_shortcode( 'csas_guest_not_allowed_message', __NAMESPACE__ . '\csas_guest_not_allowed_message_func' );
	add_shortcode( 'guest_cart_limit_reached_message', __NAMESPACE__ . '\guest_cart_limit_reached_message_func' );
	add_shortcode( 'guest_not_allowed_to_save_message', __NAMESPACE__ . '\guest_not_allowed_to_save_message_func_func' );
}

/**
 * Generates a button element.
 *
 * @param string $p_title Button title.
 * @param string $p_name  Button name.
 *
 * @return string HTML output for the button.
 */
function button_func( $p_title, $p_name ) {

	$_name_lowercase = strtolower( $p_name );
	$_wc_style_class = $_csas_options->get_use_wc_style_for_buttons() ? ' wp-element-button button' : '';
	$_html           = '<button class="csas-' . $_name_lowercase . '-cart' . $_wc_style_class . '">' . $p_title . '</button>';

	return $_html;
}

/**
 * Returns HTML for the share button.
 *
 * @return string HTML output.
 */
function share_button_func() {

	$_default_title = Options::get_option( 'csas_share_button_title', 'Share' );
	return button_func( $_default_title, 'share' );
}

/**
 * Returns HTML for the save button.
 *
 * @return string HTML output.
 */
function save_button_func() {

	$_default_title = Options::get_option( 'csas_save_button_title', 'Save' );
	return button_func( $_default_title, 'save' );
}

/**
 * Returns a message when a guest is not allowed.
 *
 * @return string Message HTML.
 */
function guest_not_allowed_message_func() {

	return '<div class="csas-guest-not-allowed-message">Guests are not allowed to perform this action.</div>';
}

/**
 * Returns message when guest cart limit is reached.
 *
 * @return string Message HTML.
 */
function guest_cart_limit_reached_message_func() {

	return '<div class="csas-guest-cart-limit-reached-message">Guest cart limit reached.</div>';
}

/**
 * Returns message when guest is not allowed to save.
 *
 * @return string Message HTML.
 */
function guest_not_allowed_to_save_message_func() {

	return '<div class="csas-guest-not-allowed-to-save-message">Guests are not allowed to save items.</div>';
}
