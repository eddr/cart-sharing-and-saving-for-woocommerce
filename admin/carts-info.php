<?php
/**
 * Admin View Carts Page.
 *
 * @package Cart_Sharing_And_Saving
 */

namespace EB\CSAS\AdminViews;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once __DIR__ . '/classes/class-saved-carts-products-list-table.php';
require_once __DIR__ . '/classes/class-saved-carts-list-table.php';

add_filter( 'woocommerce_screen_ids', __NAMESPACE__ . '\add_screen_ids' );

/**
 * Add screen IDs for WooCommerce integration.
 *
 * @param array $p_screen_ids Screen IDs.
 * @return array
 */
function add_screen_ids( $p_screen_ids ) {
	$p_screen_ids[] = 'woocommerce_page_view-saved-carts-info';
	return $p_screen_ids;
}

/**
 * Render the main Saved Carts / Products page wrapper.
 *
 * @return void
 */
function view_saved_carts_info_page() {
	// Read-only filter via GET; sanitize and ignore nonce verification recommendation.
	$tab = isset( $_GET['tab'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: 'carts';
	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Carts Info', 'cart-sharing-and-saving-for-woocommerce' ); ?></h2>
		<h2 class="nav-tab-wrapper">
			<a href="?page=view-saved-carts-info&tab=carts" class="nav-tab <?php echo ( 'carts' === $tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Carts', 'cart-sharing-and-saving-for-woocommerce' ); ?></a>
			<a href="?page=view-saved-carts-info&tab=products" class="nav-tab <?php echo ( 'products' === $tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Products', 'cart-sharing-and-saving-for-woocommerce' ); ?></a>
		</h2>
		<?php
		if ( 'carts' === $tab ) {
			view_saved_carts_page();
		} else {
			view_saved_products_carts_page();
		}
		?>
	</div>
	<?php
}

/**
 * Render the Saved Carts table page (filters via GET).
 *
 * @return void
 */
function view_saved_carts_page() {
	$saved_carts_list_table = new \EB\CSAS\Admin\ListTables\Saved_Carts_List_Table();
	$saved_carts_list_table->prepare_items();
	$saved_carts_list_table->process_bulk_action();

	// Read filters from GET (read-only). Sanitize and avoid undefined index notices.
	$user_id = 0;
	if ( isset( $_GET['customer_users'][0] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = absint( wp_unslash( $_GET['customer_users'][0] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$username = '';
	if ( 0 !== $user_id ) {
		$user     = get_user_by( 'id', $user_id );
		$username = $user ? $user->display_name : '';
	}

	// Products filter (array of IDs).
	$search_products = array();
	if ( isset( $_GET['product_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_products = array_map( 'absint', (array) wp_unslash( $_GET['product_ids'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	// Cart ID filter from GET/POST (REQUEST).
	$s_id = '';
	if ( isset( $_REQUEST['s_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s_id = sanitize_text_field( wp_unslash( $_REQUEST['s_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Saved Carts', 'cart-sharing-and-saving-for-woocommerce' ); ?></h1>
		<form method="get">
			<input type="hidden" name="page" value="view-saved-carts-info" />
			<input type="hidden" name="tab" value="carts" />
			<div class='search-segments-wrapper'>
				<div class='search-segment search-segment-product'>
					<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'cart-sharing-and-saving-for-woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
					<?php foreach ( $search_products as $pid ) : ?>
						<?php
						$product = wc_get_product( $pid );
						if ( ! $product ) {
							continue; }
						?>
						<?php $formatted_name = $product->get_formatted_name(); ?>
						<option value="<?php echo esc_attr( $pid ); ?>" selected="selected"><?php echo esc_html( $formatted_name ); ?></option>
					<?php endforeach; ?>
					</select>
				</div>
				<div class='search-segment search-segment-user'>
					<select class="wc-customer-search" id="customer_users" name="customer_users[]" data-placeholder="<?php esc_attr_e( 'Guest', 'cart-sharing-and-saving-for-woocommerce' ); ?>" data-allow_clear="true">
						<?php if ( 0 !== $user_id ) { ?>
							<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo esc_html( $username ); ?></option>
						<?php } ?>
					</select>
				</div>
				<div class='search-segment search-segment-cart-id'>
					<input type="text" name="s_id" value="<?php echo esc_attr( $s_id ); ?>" placeholder="<?php esc_attr_e( 'Search by Cart ID...', 'cart-sharing-and-saving-for-woocommerce' ); ?>" />
					<input type="submit" value="<?php esc_attr_e( 'Search', 'cart-sharing-and-saving-for-woocommerce' ); ?>" class="button" />
				</div>
			</div>
			<?php $saved_carts_list_table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render the Saved Products Carts table page (filters via GET).
 *
 * @return void
 */
function view_saved_products_carts_page() {
	$saved_carts_list_table = new \EB\CSAS\Admin\ListTables\Saved_Carts_Products_List_Table();
	$saved_carts_list_table->prepare_items();
	$saved_carts_list_table->process_bulk_action();

	// Read filters from GET (read-only). Sanitize and avoid undefined index notices.
	$user_id = 0;
	if ( isset( $_GET['customer_users'][0] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = absint( wp_unslash( $_GET['customer_users'][0] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$username = '';
	if ( 0 !== $user_id ) {
		$user     = get_user_by( 'id', $user_id );
		$username = $user ? $user->display_name : '';
	}

	$search_products = array();
	if ( isset( $_GET['product_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_products = array_map( 'absint', (array) wp_unslash( $_GET['product_ids'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Saved Products\' Carts', 'cart-sharing-and-saving-for-woocommerce' ); ?></h1>
		<form method="get">
			<input type="hidden" name="page" value="view-saved-carts-info" />
			<input type="hidden" name="tab" value="products" />
			<div class='search-segments-wrapper'>
				<div class='search-segment search-segment-product'>
					<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'cart-sharing-and-saving-for-woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
						<?php foreach ( $search_products as $pid ) : ?>
							<?php
							$product = wc_get_product( $pid );
							if ( ! $product ) {
								continue; }
							?>
							<?php $formatted_name = $product->get_formatted_name(); ?>
							<option value="<?php echo esc_attr( $pid ); ?>" selected="selected"><?php echo esc_html( $formatted_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<input type="submit" value="<?php esc_attr_e( 'Search', 'cart-sharing-and-saving-for-woocommerce' ); ?>" class="button" />
			<?php $saved_carts_list_table->display(); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_menu', __NAMESPACE__ . '\create_carts_submenu' );

/**
 * Register the CSAS admin submenu under WooCommerce.
 *
 * @return void
 */
function create_carts_submenu() {
	add_submenu_page(
		'woocommerce',
		'Saved Carts',
		'Saved Carts',
		'manage_options',
		'view-saved-carts-info',
		__NAMESPACE__ . '\view_saved_carts_info_page'
	);
}
