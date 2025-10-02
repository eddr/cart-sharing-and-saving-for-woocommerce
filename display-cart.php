<?php
/**
 * Display Saved/Shared Carts shortcodes and renderers.
 *
 * Provides shortcodes and helper functions to render saved carts, shared carts,
 * and associated action buttons.
 *
 * @package EB\CSAS
 * @subpackage Frontend\Display
 */

namespace EB\CSAS\Frontend\Display;

use EB\CSAS\Options;

add_shortcode( 'csas_display_saved_carts', __NAMESPACE__ . '\csas_display_saved_carts_sc' );
add_shortcode( 'csas_display_shared_cart', __NAMESPACE__ . '\csas_shared_cart_sc' );

/**
 * Build default buttons data (label => css class).
 *
 * Optionally accepts overrides via $p_args['buttons'].
 *
 * @param array $p_args Optional arguments (e.g., 'buttons' => [ 'Label' => 'class' ]).
 * @return array Associative array of button label => css class.
 */
function csas_get_default_buttons_data( $p_args = array() ) {

	global $_csas_options;

	$_buttons = array(
		$_csas_options->get_share_button_text()  => 'csas-share-cart',
		$_csas_options->get_load_button_text()   => 'csas-load-cart',
		$_csas_options->get_delete_button_text() => 'csas-delete-cart',
	);

	if ( ! empty( $p_args['buttons'] ) && is_array( $p_args['buttons'] ) ) {

		$_buttons = $p_args['buttons'];
	}

	return $_buttons;
}

/**
 * Output hidden inputs carrying context data.
 *
 * @param string $p_csas_hash Cart identifier hash.
 * @param int    $p_user_id   User ID associated with cart.
 * @return string HTML markup with hidden inputs.
 */
function csas_hidden_data( $p_csas_hash, $p_user_id ) {

	$_html  = '';
	$_html .= '<input type="hidden" name="csas_hash" value="' . esc_attr( $p_csas_hash ) . '">';
	$_html .= '<input type="hidden" name="csas_user" value="' . esc_attr( (string) $p_user_id ) . '">';

	return $_html;
}

/**
 * Render a group of action buttons given an associative array of label => class.
 *
 * @param array  $p_buttons Associative array of label => css class.
 * @param string $p_csas_hash Cart hash.
 * @return string HTML markup for button group.
 */
function csas_print_buttons( $p_buttons, $p_csas_hash ) {

	global $_csas_options;

	$_wc_style_class = $_csas_options->get_use_wc_style_for_buttons() ? ' wp-element-button button' : '';

	$_html  = '';
	$_html .= '<div class="csas-actions">';

	foreach ( $p_buttons as $_label => $_class ) {
		// Note: do NOT pass variables into translation functions per PHPCS.
		$_html .= '<button type="button" data-cart-key="' . $p_csas_hash . '" data-action="' . $_class . '" class="' . esc_attr( $_class . $_wc_style_class ) . '">';
		$_html .= esc_html( (string) $_label );
		$_html .= '</button>';
	}

	$_html .= '</div>';

	return $_html;
}

/**
 * Print cart contents and coupons as structured markup.
 *
 * @param string $p_cart_hash Cart hash.
 * @param array  $p_items Cart items.
 * @param array  $p_coupons Applied coupons.
 * @param array  $p_totals Totals array.
 * @param string $p_csas_hash CSAS hash.
 * @param int    $p_user_id User ID.
 * @param string $p_updated_at Updated at string.
 * @return string Rendered HTML.
 */
function print_cart_contents_and_coupons( $p_cart_hash, $p_items, $p_coupons, $p_totals, $p_csas_hash, $p_user_id, $p_updated_at = '' ) {

	$_items = maybe_unserialize( $p_items );
	$_html  = '';
	$_html .= '<section class="csas-cart-box" data-cart-key="' . esc_attr( $p_cart_hash ) . '">';

	// Header with timestamp.
	$_now_gmt = $p_updated_at;
	$_html   .= '<header class="csas-cart-header">';

	$_html .= '<p class="csas-cart-meta">' . esc_html( $_now_gmt ) . '</p>';
	$_html .= '</header>';

	// Items.
	$_html .= '<ul class="csas-cart-items">';
	if ( ! empty( $_items ) && is_array( $_items ) ) {
		$_i = 0;

		foreach ( $_items as $_item ) {

			++$_i;

			$_product_name = isset( $_item['title'] ) ? (string) $_item['title'] : '';
			$_qty          = isset( $_item['quantity'] ) ? (int) $_item['quantity'] : 0;

			$_pid = ( $_item['variation_id'] ) ? $_item['variation_id'] : $_item['product_id'];
			$_p   = wc_get_product( $_pid );

			$_product_unavailable = ( empty( $_p ) || ( ! in_array( $_p->get_status(), array( 'publish' ) ) ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$_permalink           = $_product_unavailable ? ( empty( $_p ) ? '' : $_p->get_permalink() ) : $_p->get_permalink();

			$_html .= '<li class="csas-cart-item">';
			$_html .= '<span class="csas-cart-qty">' . esc_html( (string) $_qty ) . '</span><span class="csas-times">&times;</span> ';
			$_html .= '<a href="' . $_permalink . '"><span class="csas-cart-name">' . esc_html( $_product_name ) . '</span></a>';

			$_html .= '</li>';
		}
	} else {

		$_html .= '<li class="csas-cart-item csas-cart-item-empty">' . esc_html__( 'No items.', 'cart-sharing-and-saving-for-woocommerce' ) . '</li>';
	}
	$_html .= '</ul>';

	// Coupons.
	$_coupons = maybe_unserialize( $p_coupons );

	if ( ! empty( $_coupons ) && is_array( $_coupons ) ) {

		$_coupons_html = '<div class="csas-cart-coupons">';

		$_coupons_html      .= '<p><span class="csas-coupon-prefix">' . __( 'Coupons', 'cart-sharing-and-saving-for-woocommerce' ) . '</span>';
		$_coupons_codes_html = '';
		foreach ( $_coupons as $coupon ) {

			$_coupons_codes_html .= esc_html( $coupon ) . ',';
		}
		$_coupons_html .= rtrim( $_coupons_codes_html, ',' );
		$_coupons_html .= '</p></div>';

		$_html .= $_coupons_html;

	}

	$_totals = maybe_unserialize( $p_totals );
	// Totals (print safely if provided).
	if ( ! empty( $_totals ) && is_array( $_totals ) ) {

		$_total_str = isset( $_totals['total'] ) ? (string) $_totals['total'] : '';
		if ( '' !== $_total_str ) {

			$_html .= '<div class="csas-cart-totals"><strong>' . esc_html__( 'Total', 'cart-sharing-and-saving-for-woocommerce' ) . ':</strong> ';
			$_html .= '<span class="csas-cart-total-val">' . esc_html( $_total_str ) . '</span></div>';
		}
	}

	// Buttons and hidden fields.
	$_buttons = csas_get_default_buttons_data();
	$_html   .= csas_print_buttons( $_buttons, $p_cart_hash );
	$_html   .= csas_hidden_data( $p_csas_hash, $p_user_id );

	$_html .= '</section>';

	return wp_kses_post( $_html );
}

/**
 * Render a single cart item as HTML.
 *
 * @param array $p_item Cart item data.
 * @return string HTML markup for the cart item.
 */
function csas_get_cart_item_html( $p_item ) {

	$_html = '';

	$_product_name = isset( $p_item['title'] ) ? (string) $p_item['title'] : '';
	$_qty          = isset( $p_item['quantity'] ) ? (int) $p_item['quantity'] : 0;

	$_pid = ( $p_item['variation_id'] ) ? $p_item['variation_id'] : $p_item['product_id'];
	$_p   = wc_get_product( $_pid );

	$_product_unavailable = ( empty( $_p ) || ( ! in_array( $_p->get_status(), array( 'publish' ) ) ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
	$_permalink           = $_product_unavailable ? ( empty( $_p ) ? '' : $_p->get_permalink() ) : $_p->get_permalink();

	$_html .= '<div class="csas-cart-item">';
	$_html .= '<span class="csas-cart-qty">' . esc_html( (string) $_qty ) . '</span><span class="csas-times">&times;</span> ';
	$_html .= '<a href="' . $_permalink . '"><span class="csas-cart-name">' . esc_html( $_product_name ) . '</span></a>';
	$_html .= '</div>';

	return $_html;
}

/**
 * Get HTML for all carts, either provided or current user's.
 *
 * @param array|null $p_carts Optional array of cart objects. If null, fetches current user's carts.
 * @return string HTML markup for the carts.
 */
function get_carts_html( $p_carts = null ) {

	global $_csas_options;

	$_csas_ob = new \EB\CSAS\Logic();
	$_carts   = is_null( $p_carts ) ?
				$_csas_ob->get_current_carts_data( array( 'allow_expired' => true ) )
				: $p_carts;

	$_belongs_to_current_user = true;
	$_buttons_args            = array( 'belongs_to_current_user' => $_belongs_to_current_user );
	$_buttons                 = csas_get_default_buttons_data( $_buttons_args );

	$_show_coupons = $_csas_options->get_share_applied_coupons();

	$_bypassing_html = apply_filters( __NAMESPACE__ . '\get_saved_carts_html', null, $_carts ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	if ( ! is_null( $_bypassing_html ) ) {

		return $_bypassing_html;
	}

	// Display logic for the carts.
	$_output = '<div class="csas-carts-wrapper">';

	if ( ! empty( $_carts ) ) {

		$_counter = 1;

		foreach ( $_carts as $cart_contents ) {

			$_output_cart_head     = '';
			$_output_cart_contents = '';
			$_csas_hash            = $cart_contents->csas_hash;

			if ( $cart_contents ) {

				$_items = maybe_unserialize( $cart_contents->items );

				if ( ! empty( $_items ) ) {

					$_total_quantity = 0;

					$_output_cart_contents .= '<div class="csas-cart-items">';
					foreach ( $_items as $item_key => $item ) {

						$_output_cart_contents .= csas_get_cart_item_html( $item );

						$_total_quantity += intval( $item['quantity'] );

					}
					$_output_cart_contents .= '</div>'; // .csas-cart-items

					if ( $_show_coupons ) {

						$_coupons = maybe_unserialize( $cart_contents->coupons );

						if ( ! empty( $_coupons ) ) {

							$_output_cart_contents .= '<div class="csas-cart-coupons">';
							$_output_cart_contents .= '<p><span class="csas-coupon-prefix">' . __( 'Coupons', 'cart-sharing-and-saving-for-woocommerce' ) . ':</span>';
							foreach ( $_coupons as $coupon ) {

								$_output_cart_contents .= esc_html( $coupon ) . ',';
							}
							$_output_cart_contents  = rtrim( $_output_cart_contents, ',' );
							$_output_cart_contents .= '</p></div>';
						}
					}

					$_totals = maybe_unserialize( $cart_contents->totals );

					// Totals.
					if ( ! empty( $_totals ) && is_array( $_totals ) ) {

						$_total_str = isset( $_totals['total'] ) ? (string) $_totals['total'] : '';
						if ( '' !== $_total_str ) {

							$_output_cart_contents .= '<div class="csas-cart-totals"><strong>' . esc_html__( 'Total:', 'cart-sharing-and-saving-for-woocommerce' ) . '</strong> ';
							$_output_cart_contents .= '<span class="csas-cart-total-val">' . esc_html( $_total_str ) . '</span></div>';
						}
					}

					// buttons.
					//
					$_output_cart_contents .= csas_print_buttons( $_buttons, $_csas_hash );

					$_cart_name = '#' . ( $_counter++ );

					$_output_cart_head = "<div class='csas-cart-box' data-cart-key='$_csas_hash'><h2>" . __( 'Cart', 'cart-sharing-and-saving-for-woocommerce' ) . " $_cart_name</h2>";
					$_output          .= $_output_cart_head . $_output_cart_contents . '</div>';
				}
			}
		}
	}

	$_output .= '</div>';

	return $_output;
}
/**
 * Shortcode handler for displaying saved carts.
 *
 * @return string HTML.
 */
function csas_display_saved_carts_sc() {

	global $_csas_options;

	if ( ! is_user_logged_in()
		&& ! $_csas_options->get_allow_guests_ops() ) {

		return;
	}

	$_output = '<div class="csas-carts-placeholder" style="padding:0 !important;margin:0 !important"></div>';

	return $_output;
}

/**
 * Format a date string from the cart data.
 *
 * @param string $p_date_str Date string to format.
 * @return string Formatted date string.
 */
function format_cart_date( $p_date_str ) {

	$_old_date_timestamp = strtotime( $p_date_str );
	$_new_date           = gmdate( 'd-m-Y', $_old_date_timestamp );

	return $_new_date;
}
/**
 * Shortcode: display_shared_cart.
 *
 * Displays a shared cart based on query var 'csas_cid' or provided attributes.
 *
 * @param array $p_atts Shortcode attributes.
 * @return string Rendered HTML for the shared cart (or message when not found).
 */
function csas_shared_cart_sc( $p_atts ) {

	$_html = '';

	// Prefer query var when present.
	$_csas_hash = get_query_var( 'csas_cid' );
	if ( empty( $_csas_hash ) && isset( $p_atts['cid'] ) ) {

		$_csas_hash = (string) $p_atts['cid'];
	}

	if ( empty( $_csas_hash ) ) {

		$_html = esc_html__( 'No cart to share.', 'cart-sharing-and-saving-for-woocommerce' );
		return $_html;
	}

	// Fetch cart via plugin object if available.
	$_cart_data = array();
	$_user_id   = 0;
	$_csas_ob   = new \EB\CSAS\Logic();

	if ( isset( $_csas_ob )
		&& is_object( $_csas_ob )
		&& method_exists( $_csas_ob, 'get_cart_saved_for_csas_hash' )
		) {

		$_cart_data = $_csas_ob->get_cart_saved_for_csas_hash( $_csas_hash, false );
	}

	if ( empty( $_cart_data ) ) {

		$_html = esc_html__( 'No cart to share.', 'cart-sharing-and-saving-for-woocommerce' );
	} else {

		$_bypassing_html = apply_filters( __NAMESPACE__ . '\get_shared_cart_html', null, $_cart_data ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		if ( ! is_null( $_bypassing_html ) ) {

			return $_bypassing_html;
		}

		$_items      = isset( $_cart_data->items ) ? $_cart_data->items : array();
		$_coupons    = isset( $_cart_data->coupons ) ? $_cart_data->coupons : array();
		$_totals     = isset( $_cart_data->totals ) ? $_cart_data->totals : array();
		$_user_id    = isset( $_cart_data->object_id ) ? (int) $_cart_data->object_id : 0;
		$_updated_at = isset( $_cart_data->updated_at ) ? $_cart_data->updated_at : '';

		$_html = print_cart_contents_and_coupons(
			$_csas_hash,
			$_items,
			$_coupons,
			$_totals,
			$_csas_hash,
			$_user_id,
			$_updated_at
		);
	}

	return $_html;
}

/**
 * REST callback: return the HTML produced by get_carts_html().
 *
 * Endpoint: GET /wp-json/csas/v1/carts-html
 *
 * @param \WP_REST_Request $p_request Request object (unused).
 * @return void
 */
function rest_get_carts_html( \WP_REST_Request $p_request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

	$_html = get_carts_html();

	wp_send_json_success(
		array(
			'html' => $_html,
		)
	);
}

/**
 * Register CSAS REST API routes.
 *
 * @return void
 */
function register_rest_routes() {

	register_rest_route(
		'csas/v1',
		'/carts-html',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\rest_get_carts_html',
				'permission_callback' => __NAMESPACE__ . '\rest_permission_public',
				// If you later add query args, define 'args' here with sanitization/validation.
			),
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );

/**
 * Permission callback for the carts HTML endpoint.
 *
 * Read-only public endpoint returning HTML; adjust if you want to restrict.
 *
 * @return bool
 */
function rest_permission_public() {
	return true;
}
