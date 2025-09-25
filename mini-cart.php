<?php
/**
 * Mini cart display helpers for CSAS.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\Mini_Cart;

use EB\CSAS\Options;

// TODO: why here? move buttons to a different file and namespace.
// .
add_action( 'woocommerce_loaded', __NAMESPACE__ . '\init_display_buttons' );

/**
 * Initialize display of mini cart buttons.
 *
 * @return void
 */
function init_display_buttons() {

	$_mini_cart_visibility = $_csas_options->get_mini_cart_visibility();

	if ( 'manual' !== $_mini_cart_visibility ) {

		$_loc = ( 'bottom' === $_mini_cart_visibility ) ? 'after' : 'before';
		add_action( 'woocommerce_widget_shopping_cart_' . $_loc . '_buttons', __NAMESPACE__ . '\add_custom_cart_buttons' );

	}
}

/**
 * Add buttons to the WooCommerce cart page.
 *
 * @return void
 */
function add_custom_cart_buttons() {

	$_wc_style_class = $_csas_options->get_use_wc_style_for_buttons() ? ' wp-element-button button' : '';

	$_html = '';

	$_html .= '<nav class="csas-cart-ops-wrapper">';
	$_html .= '<button class="csas-save-cart' . $_wc_style_class . '">' . __( 'Save Cart', 'cart-sharing-and-saving-for-woocommerce' ) . '</button>';  // Save Cart Button.
	$_html .= '<button class="csas-share-cart' . $_wc_style_class . '">' . __( 'Share Cart', 'cart-sharing-and-saving-for-woocommerce' ) . '</button>';  // Share Cart Button.
	$_html .= '</nav>';

	echo $_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
