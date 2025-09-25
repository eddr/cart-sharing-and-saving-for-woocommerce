<?php
/**
 * Cart page helpers for CSAS.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\Cart_Page;

use EB\CSAS\Options;

add_action( 'woocommerce_loaded', __NAMESPACE__ . '\init_display_buttons' );

/**
 * Initialize display of cart page buttons.
 *
 * @return void
 */
function init_display_buttons() {

	global $_csas_options;

	$_cart_visibility     = $_csas_options->get_cart_page_visibility();
	$_checkout_visibility = $_csas_options->get_checkout_page_visibility();

	if ( 'manual' !== $_cart_visibility ) {

		$_loc = ( 'bottom' === $_cart_visibility ) ? 'after' : 'before';
		add_action( 'woocommerce_' . $_loc . '_cart_table', __NAMESPACE__ . '\add_custom_cart_buttons' );
	}

	if ( 'manual' !== $_checkout_visibility ) {

		$_loc = ( 'bottom' === $_checkout_visibility ) ? 'after' : 'before';
		add_action( 'woocommerce_' . $_loc . '_checkout_form', __NAMESPACE__ . '\add_custom_cart_buttons' );

	}
}

/**
 * Add buttons to the WooCommerce cart page.
 *
 * @return void
 */
function add_custom_cart_buttons() {

	global $_csas_options;
	$_wc_style_class = $_csas_options->get_use_wc_style_for_buttons() ? ' wp-element-button button' : '';
	$_share_title    = $_csas_options->get_share_button_text();
	$_save_title     = $_csas_options->get_save_button_text();

	$_html = '';

	$_html .= '<nav class="csas-cart-ops-wrapper">';
	$_html .= '<button class="csas-save-cart' . $_wc_style_class . '">' . $_save_title . '</button>';  // Save Cart Button.
	$_html .= '<button class="csas-share-cart' . $_wc_style_class . '">' . $_share_title . '</button>';  // Share Cart Button.
	$_html .= '</nav>';

	echo wp_kses_post( $_html );
}
