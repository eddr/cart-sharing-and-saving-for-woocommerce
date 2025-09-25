<?php
/**
 * HPOS compatibility declaration.
 *
 * @package EBCSAS
 */

add_action( 'before_woocommerce_init', 'csas_hpos_compatibility' );

/**
 * Declare compatibility with WooCommerce HPOS (custom order tables).
 *
 * @return void
 */
function csas_hpos_compatibility() {

	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
