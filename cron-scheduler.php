<?php
/**
 * Cron scheduler utilities for CSAS.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\CRON_SCHEDULER;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Activates scheduling via WC action-scheduler
 *
 * @return void
 */
function schedule_carts_processing() {

	if ( function_exists( 'as_has_scheduled_action' ) ) {

		if ( false === as_has_scheduled_action( '\EB\CSAS\LOGIC\process_carts_expiration' ) ) {

			$_cron_action_interval = HOUR_IN_SECONDS;
			as_schedule_single_action( strtotime( "+ $_cron_action_interval seconds" ), '\EB\CSAS\LOGIC\process_carts_expiration' );

		}
	}
}

// Hooking to cron events.
//
global $_csas_options;
if ( ( $_csas_options->get_is_enabled() ) ) {

	$_number_of_hours = $_csas_options->get_cart_expiration();
	if ( ! empty( $_number_of_hours ) ) {
		// Queue processing.
		//
		add_action( 'init', __NAMESPACE__ . '\schedule_carts_processing' );
	}
}
