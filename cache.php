<?php
/**
 * Cache integration helpers for CSAS.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\Cache;

use EB\CSAS\Options;

add_action( 'init', __NAMESPACE__ . '\hooks' );

/**
 * Hooks.
 *
 * @return void.
 */
function hooks() {

	add_action( 'csas_current_user_carts_changed', __NAMESPACE__ . '\cart_changed' );
	add_action( 'send_headers', __NAMESPACE__ . '\add_no_cache_headers_to_specific_post' );
}

/**
 * Add no-cache headers to a specific post (the saved carts page).
 *
 * @return void
 */
function add_no_cache_headers_to_specific_post() {

	global $_csas_options;
	$_target_post_id = $_csas_options->get_saved_carts_page_id();

	// Check if we are on the singular page of the specific post ID.
	if ( is_singular() &&
		( get_the_ID() === (int) $_target_post_id ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison {

		// Set no-cache headers.
		nocache_headers();
	}
}

/**
 * Cart changed.
 *
 * @param string $p_cart_key Cart key.
 * @return void.
 */
function cart_changed( $p_cart_key ) {

	global $_csas_options;

	$_share_page_ids = array_values( $_csas_options->get_all_share_cart_pages_ids() );
	$_saved_page_ids = array_values( $_csas_options->get_all_saved_carts_pages_ids() );

	$_urls_to_flush = array();
	$_ids_to_flush  = array();

	foreach ( $_share_page_ids as $id ) {

		$_urls_to_flush[] = trailingslashit( \EB\CSAS\Lang::get_permalink_in_assigned_language( $id ) ) . $p_cart_key;
	}

	flush_cache( $_ids_to_flush, $_urls_to_flush );

	// Flush current user's cache.
	//
	$_cache_key = get_cache_key_for_current_logged_in_user();
	if ( ! empty( $_cache_key ) ) {

		wp_cache_delete( $_cache_key, 'csas' );
	}
}

/**
 * Flush carts page cache.
 *
 * @return void
 */
function flush_carts_page_cache() {

	global $_csas_options;

	$_saved_page_ids = array_values( $_csas_options->get_all_saved_carts_pages_ids() );

	flush_cache( $_saved_page_ids );
}

/**
 * Flush cache for given post IDs and URLs using the currently active cache plugin.
 *
 * @param array $p_ids posts' ids.
 * @param array $p_urls urls.
 * @return void
 */
function flush_cache( $p_ids = array(), $p_urls = array() ) {

	if ( ! empty( $p_ids ) ) {
		foreach ( $p_ids as $pid ) {

			flush_post( $pid );
		}
	}

	if ( ! empty( $p_urls ) ) {
		foreach ( $p_urls as $url ) {

			flush_url( $url );
		}
	}
}
/**
 * Flush URL cache using the currently active cache plugin.
 *
 * @param string $p_url The URL to flush.
 * @return void
 */
function flush_url( $p_url ) {

	// Special case: W3TC.
	//
	if ( class_exists( 'W3TC\PgCache_Plugin' ) ) {

		do_action( 'w3tc_flush_url', $p_url );
		do_action( 'w3tc_flush_url', trailingslashit( $p_url ) );

		w3tc_flush_url( $p_url );
		w3tc_flush_url( trailingslashit( $p_url ) );

	}

	// Special case: WP Fastest Cache.
	//
	if ( class_exists( 'WpFastestCache' ) ) {

		$wp_fastest_cache = new WpFastestCache();
		$wp_fastest_cache->singleDeleteCache( $p_url );
	}

	// Special case: WP Super Cache.
	//
	if ( function_exists( 'wpsc_delete_url_cache' ) ) {

		wpsc_delete_url_cache( $p_url );
	}

	// Special case: LiteSpeed Cache.
	//
	if ( class_exists( '\LiteSpeed\Purge' ) ) {

		do_action( 'litespeed_purge_url', $p_url );
	}

	if ( function_exists( 'rocket_clean_files' ) ) {

		rocket_clean_files( $p_url );
	}
}
/**
 * Flush post using the currently active cache plugin.
 *
 * @param int $p_id post id.
 * @return void
 */
function flush_post( $p_id ) {

	// Special case: W3TC.
	//
	if ( class_exists( 'W3TC\PgCache_Plugin' ) ) {

		do_action( 'w3tc_flush_post', $p_id );
	}

	// Special case: WP Fastest Cache.
	//
	if ( class_exists( 'WpFastestCache' ) ) {

		wpfc_clear_post_cache_by_id( $p_id );
	}

	// Special case: WP Super Cache.
	//
	if ( function_exists( 'wpsc_delete_post_cache' ) ) {

		wpsc_delete_post_cache( $p_id );
	}

	// Special case: LiteSpeed Cache.
	//
	if ( class_exists( '\LiteSpeed\Purge' ) ) {

		do_action( 'litespeed_purge_post', $p_id );
	}

	// Special case: WP Rocket.
	//
	if ( function_exists( 'rocket_clean_post' ) ) {

		rocket_clean_post( $p_id );
	}
}

/**
 * Get cache key for current logged in user.
 *
 * @return string Cache key.
 */
function get_cache_key_for_current_logged_in_user() {

	$_user_id = get_current_user_id();

	if ( ! empty( $_user_id ) ) {

		return 'csas_user_carts_' . $_user_id;
	}

	return '';
}

/**
 * Get cache key for current user
 *
 * @return string Cache key.
 */
function get_cache_key_for_current_user() {

	$_id = \EB\CSAS\Logic::get_woocommerce_session_id();

	return 'csas_user_carts_' . $_id;
}
