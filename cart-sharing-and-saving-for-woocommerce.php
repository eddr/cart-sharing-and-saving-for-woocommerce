<?php
/**
 * Plugin Name: Cart Sharing and Saving For WooCommerce
 * Plugin URI:
 * Description: A plugin to save and share user carts for logged-in users and guests.
 * Version: 0.5
 * Author: Earlybirds
 * Author URI:
 * Text Domain: cart-sharing-and-saving-for-woocommerce
 * Domain Path: /languages
 * License: GPLv3
 *
 * Woo:
 * WC requires at least: 5.2.X
 * WC tested up to: 9.3.3
 *
 * @package Cart_Sharing_And_Saving
 */

use EB\CSAS\Options;

require_once 'db.php';
require_once 'class-lang.php';
require_once 'cache.php';
require_once 'hpos.php';
require_once wp_normalize_path( __DIR__ . '/admin/class-options.php' );

if ( is_admin() ) {

	require_once wp_normalize_path( __DIR__ . '/admin/options-page.php' );
	require_once wp_normalize_path( __DIR__ . '/admin/carts-info.php' );
	require_once wp_normalize_path( __DIR__ . '/blocks.php' );
}
require_once 'shortcodes.php';

global $_csas_options;
if ( $_csas_options->get_is_enabled() ) {

	require_once 'class-hash.php';
	require_once 'class-queries.php';
	require_once 'class-logic.php';
	require_once 'display-cart.php';
	require_once 'rewrite-carts.php';
	require_once 'cart-page.php';
	require_once 'cron-scheduler.php';

	add_action( 'init', 'csas_load' );
}

/**
 * Activation Hook.
 *
 * @return void
 */
function csas_activate() {
}

/**
 * Deactivation Hook.
 *
 * @return void
 */
function csas_deactivate() {
}

register_activation_hook( __FILE__, 'csas_activate' );
register_deactivation_hook( __FILE__, 'csas_deactivate' );

/**
 * Csas plugin links.
 *
 * @param array $p_actions Plugin action links.
 * @return array.
 */
function csas_plugin_links( $p_actions ) {
	// Create the links.
	$_custom_links = array(
		'<a href="' . admin_url( 'admin.php?page=ebcsas-options' ) . '">' . __( 'Settings', 'cart-sharing-and-saving-for-woocommerce' ) . '</a>',
	);

	return array_merge( $_custom_links, $p_actions );
}

// Get the plugin file basename.
$_plugin_file = plugin_basename( __FILE__ );


add_filter( 'plugin_action_links_' . $_plugin_file, 'csas_plugin_links' );
add_action( 'wp_enqueue_scripts', 'csas_enqueue_scripts' );

/**
 * Csas load.
 *
 * @return void.
 */
function csas_load() {

	new \EB\CSAS\Logic();
}

/**
 * Csas enqueue scripts.
 *
 * @return void.
 */
function csas_enqueue_scripts() {

	global $_csas_options;

	wp_enqueue_style( 'dashicons' );
	wp_enqueue_script( 'csas_dialogbox', plugins_url( '/assets/js/xdialog.min.js', __FILE__ ), array(), time(), false );
	wp_enqueue_script( 'csas_main', plugins_url( '/assets/js/main.js', __FILE__ ), array( 'jquery' ), time(), false );

	wp_enqueue_style( 'csas_style', plugins_url( '/assets/css/main.css', __FILE__ ), array(), time() );
	wp_enqueue_style( 'csas_carts_style', plugins_url( '/assets/css/carts.css', __FILE__ ), array(), time() );
	wp_enqueue_style( 'csas_dialogbox_style', plugins_url( '/assets/css/xdialog.min.css', __FILE__ ), array(), time() );

	$_logic = new \EB\CSAS\Logic();

	/**
	 * Localize the script with nonce data
	 */
	$_csas_ajax_object = array(
		'cart_ops_nonce' => wp_create_nonce( 'csas_cart_ops' ),
	);
	$_cart_data        = array(
		'wc_cart_hash'      => WC()->cart->get_cart_hash(),
		'current_csas_hash' => $_logic->get_current_cart_csas_hash(),
	);
	$_csas_data        = array(
		'cart_data'        => $_cart_data,
		'csas_ajax_object' => $_csas_ajax_object,
		'texts'            => $_csas_options->get_all_text_messages( true ),
		'titles'           => $_csas_options->get_all_front_titles(),
		'static_texts'     => array(
			'close' => esc_html__( 'Close', 'cart-sharing-and-saving-for-woocommerce' ),
		),
		'urls'             => array(
			'share_page_url'       => get_permalink( $_csas_options->get_share_cart_page_id() ),
			'saved_carts_page_url' => get_permalink( $_csas_options->get_saved_carts_page_id() ),
			'plugin_images_url'    => plugins_url( '/assets/images/', __FILE__ ),
			'copy_link_url'        => plugins_url( '/assets/images/clipboard.png', __FILE__ ),
		),
		'imgs'             => array( 'clipboard' => plugins_url( '/assets/images/clipboard.png', __FILE__ ) ),
	);
	wp_localize_script(
		'csas_main',
		'csas_data',
		$_csas_data
	);

	wp_set_script_translations(
		'csas_main',
		'cart-sharing-and-saving-for-woocommerce',
		plugin_dir_path( __FILE__ ) . 'languages'
	);
}
