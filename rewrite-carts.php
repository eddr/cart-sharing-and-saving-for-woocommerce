<?php
/**
 * CSAS rewrite rules and query vars.
 *
 * @package CSAS
 */

namespace EB\CSAS\Rewrite;

use EB\CSAS;

add_action( 'init', __NAMESPACE__ . '\csas_custom_rewrite_rule' );
add_filter( 'query_vars', __NAMESPACE__ . '\csas_custom_query_vars' );
/**
 * Registers CSAS rewrite tags and rules.
 *
 * @return void
 */
function csas_custom_rewrite_rule() {

	global $_csas_options;

	$_page_ids = $_csas_options->get_all_share_cart_pages_ids();

	add_rewrite_tag( '%csas_cid%', '(.+)' );

	foreach ( $_page_ids as $lang => $page_id ) {

		$_page_name = get_post_field( 'post_name', $page_id );

		// Skip if page slug not found.
		//
		if ( empty( $_page_name ) ) {
			continue;
		}

		add_rewrite_rule(
			$_page_name . '/(.+)/?$',
			'index.php?pagename=' . $_page_name . '&csas_cid=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			urldecode( $_page_name ) . '/(.+)/?$',
			'index.php?pagename=' . $_page_name . '&csas_cid=$matches[1]',
			'top'
		);

	}
}
/**
 * Adds CSAS query variables.
 *
 * @param array $p_vars Query vars.
 * @return array
 */
function csas_custom_query_vars( $p_vars ) {

	$p_vars[] = 'csas_cid';
	return $p_vars;
}
