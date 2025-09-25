<?php
/**
 * Blocks related functions.
 *
 * @package EBCSAS
 */

namespace EB\CSAS\Blocks;

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_blocks' );

/**
 * Enqueue block editor assets.
 *
 * @return void
 */
function enqueue_blocks() {

	wp_enqueue_script(
		'csas-btn-variation',
		plugins_url( '/editor/csas-btn.js', __FILE__ ),
		array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks', 'wp-compose', 'wp-element' ),
		'1.0',
		true
	);
}
