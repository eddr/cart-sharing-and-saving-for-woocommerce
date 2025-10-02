<?php
/**
 * Options page functionality and settings.
 *
 * @package EBCSAS
 */

namespace EB\CSAS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use EB\CSAS;

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_styles' );
add_action( 'admin_menu', __NAMESPACE__ . '\create_submenu' );
add_action( 'admin_init', __NAMESPACE__ . '\handle_submission' );
add_filter( 'woocommerce_screen_ids', __NAMESPACE__ . '\add_screen_ids' );
/**
 * Enqueues options page admin styles
 *
 * @param [type] $p_hook The relevant options page hook.
 * @return void
 */
function enqueue_admin_styles( $p_hook ) {

	$_eligible_hooks = array(
		'woocommerce_page_view-saved-carts-info',
		'woocommerce_page_ebcsas-options',
	);
	if ( in_array( $p_hook, $_eligible_hooks, true ) ) {

		wp_enqueue_style( 'csas-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), time() );
	}
}
/**
 * Adds the submenu.
 */
function create_submenu() {

	add_submenu_page(
		'woocommerce',
		'Cart Sharing and Saving',
		'Cart Sharing and Saving',
		'manage_options',
		'ebcsas-options',
		__NAMESPACE__ . '\settings_page'
	);

	// call register settings function.
	//
	add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
}

/**
 * Sanitize + merge plugin options, separating global and language-specific keys.
 *
 * - Global keys are stored under the special bucket "__global".
 * - Language-specific keys are stored under the current language slug.
 * - Existing keys are merged (not replaced) to avoid losing other languages.
 *
 * You can declare which keys are global via the 'csas_global_option_keys' filter:
 *   add_filter( 'csas_global_option_keys', function ( $keys ) {
 *       $keys[] = 'license_key';
 *       $keys[] = 'enable_feature_x';
 *       return $keys;
 *   } );
 *
 * @param array $p_input Raw input from the settings form (mix of global + local fields).
 * @return array Sanitized and merged settings array.
 */
function sanitize_options( $p_input ) {

	static $_csas_sanitize_options_in_progress = false;
	if ( $_csas_sanitize_options_in_progress ) {
		// Prevent re-entrancy in the same request.
		return get_option( 'csas_options', array() );
	}
	$_csas_sanitize_options_in_progress = true;

	global $_csas_options;

	$_stored = $_csas_options->get_options();
	$_lang   = \EB\CSAS\Lang::get_current_lang();

	// Define which fields are global. Extend via filter.
	$_global_keys = apply_filters(
		'csas_global_option_keys',
		array(
			'is_enabled'               => '0',
			'allow_saving'             => '0',
			'allow_sharing'            => '0',
			'allow_guests_ops'         => '0',
			'share_applied_coupons'    => '0',
			'guest_cart_limit'         => '0',
			'user_cart_limit'          => '0',
			'cart_expiration_hours'    => '24',
			'cart_expiration'          => '0',
			'cart_page_visibility'     => 'manual',
			'checkout_page_visibility' => 'manual',
			'mini_cart_visibility'     => 'manual',
			'share_coupons'            => '0',
		)
	);

	// Safety: normalize containers.
	//
	if (
		! isset( $_stored[ $_lang ] )
		|| ! is_array( $_stored[ $_lang ] )
	) {
		$_stored[ $_lang ] = array();
	}

	// Split incoming values into global vs language-specific.
	//
	$_incoming_global = array();
	foreach ( $_global_keys as $key => $value ) {

		$_incoming_global[ $key ] = $p_input[ $key ] ?? $value;
	}
	$_incoming_local = array_diff_key( (array) $p_input, $_global_keys );

	// Merge (do not wipe other languages / existing values).
	//
	$_stored           = array_merge( $_stored, $_incoming_global );
	$_stored[ $_lang ] = array_merge( $_stored[ $_lang ] ?? array(), $_incoming_local );

	return $_stored;
}

/**
 * Registers the settings parameters.
 *
 * @return void
 */
function register_settings() {

	global $_csas_options;

	$_options_prefix = $_csas_options->get_options_prefix();

	register_setting(
		'ebcsas-settings-group',
		$_options_prefix,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_options',
		)
	);
}

/**
 * Adds plugin's screen IDs to WC pages list.
 * Done in order to receive built-in WC funcionality like Ajax select boxes.
 *
 * @param array $p_screen_ids Screen IDs.
 * @return array Screen IDs.
 */
function add_screen_ids( $p_screen_ids ) {

	$p_screen_ids[] = 'woocommerce_page_ebcsas-options';

	return $p_screen_ids;
}
/**
 * Creates standard checkbox option.
 *
 * @param string  $p_name input field name.
 * @param boolean $p_checked already checked or not.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_checkbox( $p_name, $p_checked, $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '<input type="checkbox" value="1" id="' . $p_name . '" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" ' . ( $p_checked ? 'checked="checked"' : '' ) . '/>';

	return $_html;
}

/**
 * Creates standard number option.
 *
 * @param string  $p_name input field name.
 * @param boolean $p_value current value.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_number_option( $p_name, $p_value, $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '<input type="number" id="' . $p_name . '" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" value="' . esc_attr( $p_value ) . '" />';

	return $_html;
}

/**
 * Creates standard textarea option. A new version.
 *
 * @param string  $p_name input field name.
 * @param string  $p_field_name current value.
 * @param string  $p_default_field Default value option field name.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_textarea_option_v2( $p_name, $p_field_name, $p_default_field = '', $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_text        = call_user_func( array( $_csas_options, 'get_' . $p_field_name ), false );
	$_placeholder = '';

	if ( empty( $p_default_field ) ) {
		$p_default_field = explode( '_message', $p_field_name )[0];
	}
	$_placeholder = $_csas_options->get_default_message( $p_default_field );

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '<textarea placeholder="' . ( esc_html( $_placeholder ) ) . '" type="text" id="' . $p_name . '" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" />' . ( empty( $_text ) ? '' : esc_attr( $_text ) ) . '</textarea>';

	return $_html;
}
/**
 * Creates standard textarea option.
 *
 * @param string  $p_name input field name.
 * @param boolean $p_value current value.
 * @param string  $p_placeholder Placeholder text.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_textarea_option( $p_name, $p_value, $p_placeholder = '', $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '<textarea placeholder="' . esc_html( $p_placeholder ) . '" type="text" id="' . $p_name . '" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" />' . esc_attr( $p_value ) . '</textarea>';

	return $_html;
}

/**
 * Creates standard text option.
 *
 * @param string  $p_name input field name.
 * @param boolean $p_value current value.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_text_option( $p_name, $p_value, $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '<input type="text" id="' . $p_name . '" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" value="' . esc_attr( $p_value ) . '" />';

	return $_html;
}

/**
 * Creates standard radiobox option.
 *
 * @param string  $p_name input field name.
 * @param string  $p_checked_key The selected radiobox option's key.
 * @param array   $p_options All radiobox options.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_radiobox_option( $p_name, $p_checked_key, $p_options, $p_options_prefix = null, $p_use_lang = false ) {

	global $_csas_options;

	$_options_prefix = empty( $p_options_prefix ) ?
						$_csas_options->get_options_prefix() : $p_options_prefix;
	$_options_suffix = $p_use_lang ? get_lang_suffix() : '';

	$_html = '';
	foreach ( $p_options as $key => $label ) {
		$_html .= '<div class="radio-option-wrapper">';
		$_html .= '<input type="radio" name="' . esc_html( $_options_prefix ) . '[' . $p_name . ']' . $_options_suffix . '" value="' . esc_html( $key ) . '" ' . esc_html( ( $p_checked_key === $key ) ? 'checked' : '' ) . ' />';
		$_html .= '<label>' . $label . '</label>';
		$_html .= '</div>';
	}

	return $_html;
}

/**
 * Creates standard checkbox option HTML.
 *
 * @param string  $p_name input field name.
 * @param string  $p_checked already checked or not.
 * @param string  $p_description Checkbox description.
 * @param string  $p_options_prefix option name prefix.
 * @param boolean $p_use_lang true if language should be added to field's name.
 * @return string The HTML.
 */
function create_checkbox_option_html( $p_name, $p_checked, $p_description = '', $p_options_prefix = null, $p_use_lang = false ) {

	$_html = '';

	$_html .= '<div class="setting-text">
		<label for="' . $p_name . '">' . $p_description . '</label>';
	$_html .= wc_help_tip( $p_description, true );
	$_html .= '</div>
	<div class="setting-value">' .
		create_checkbox( $p_name, $p_checked, null, $p_use_lang ) .
	'</div>';

	return $_html;
}

/**
 * Creates standard text option HTML.
 *
 * @param string  $p_name input field name.
 * @param string  $p_value field's value.
 * @param string  $p_description Checkbox description.
 * @param boolean $p_create_helptip true if wc_help_tip should be added.
 * @return string The HTML.
 */
function create_text_option_html( $p_name, $p_value, $p_description = '', $p_create_helptip = true ) {

	$_html = '';

	$_html .= '<div class="setting-text"><label for="' . $p_name . '">' . $p_description . '</label>';
	if ( $p_create_helptip ) {
		$_html .= wc_help_tip( $p_description, true );
	}
	$_html .= '</div>';
	$_html .= '<div class="setting-value">' .
		create_text_option( $p_name, $p_value ) .
	'</div>';

	return $_html;
}

/**
 * Creates standard textarea option HTML.
 *
 * @param string $p_name input field name.
 * @param string $p_value field's text.
 * @param string $p_placeholder Placeholder text.
 * @param string $p_description Label's description.
 * @param string $p_label_classes label's classes.
 * @param string $p_input_classes input's classes.
 * @return string The HTML.
 */
function create_textarea_option_html( $p_name, $p_value, $p_placeholder = '', $p_description = '', $p_label_classes = '', $p_input_classes = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

	$_html = '';

	$_html .= '<div class="setting-text">
		<label for="' . $p_name . '">' . $p_description . '</label>
	</div>
	<div class="setting-value">' .
		create_textarea_option( $p_name, $p_value ) .
	'</div>';

	return $_html;
}
/**
 * Returns current language suffix.
 *
 * @return string The language suffix.
 */
function get_lang_suffix() {

	$_lang = \EB\CSAS\Lang::get_current_lang();

	return '[' . $_lang . ']';
}
/**
 * Returns current language.
 *
 * @return string The language.
 */
function get_lang() {

	$_lang = explode( '_', get_locale() )[0];

	return $_lang;
}
/**
 * Returns allowed HTML for wp_kses fitlering.
 *
 * @return array wp_kses allowed HTML array.
 */
function get_allowed_html() {

	$_allowed_html = array(
		'div'      => array(
			'class'    => array(),
			'id'       => array(),
			'style'    => array(),
			'data-tip' => array(),
		),
		'input'    => array(
			'type'    => array(),
			'name'    => array(),
			'value'   => array(),
			'checked' => array(),
			'class'   => array(),
			'id'      => array(),
			'style'   => array(),
		),
		'textarea' => array(
			'type'        => array(),
			'name'        => array(),
			'value'       => array(),
			'checked'     => array(),
			'placeholder' => array(),
			'a'           => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'class'  => array(),
				'id'     => array(),
				'style'  => array(),
				'rel'    => array(),
			),
		),
		'label'    => array(),
		'a'        => array(
			'href'   => array(),
			'title'  => array(),
			'target' => array(),
			'class'  => array(),
			'id'     => array(),
			'style'  => array(),
			'rel'    => array(),
		),
		'span'     => array(
			'class'      => array(),
			'id'         => array(),
			'style'      => array(),
			'aria-label' => array(),
			'tabindex'   => array(),
			'data-tip'   => array(),
		),
	);

	return $_allowed_html;
}

/**
 * Main settings page HTML.
 *
 * @return void
 */
function settings_page() {

	global $_csas_options;

	$_options_prefix = $_csas_options->get_options_prefix();
	$_plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/cart-sharing-and-saving/cart-sharing-and-saving-for-woocommerce.php' );
	$_plugin_version = $_plugin_data['Version'];

	$_lang           = get_lang();
	$_options_suffix = '';

	$_description = esc_html__( 'Allows sharing and saving WooCommerce carts', 'cart-sharing-and-saving-for-woocommerce' );

	$_title = esc_html__( 'Cart Sharing and Saving', 'cart-sharing-and-saving-for-woocommerce' );

	$_is_enabled_val     = $_csas_options->get_is_enabled();
	$_is_enabled_checked = $_is_enabled_val ? 'checked' : '';

	$_allow_saving_checked            = $_csas_options->get_allow_cart_saving();
	$_allow_sharing_checked           = $_csas_options->get_allow_cart_sharing();
	$_allow_guests_save_carts_checked = $_csas_options->get_allow_guests_ops();
	$_share_applied_coupons           = $_csas_options->get_share_applied_coupons();
	$_use_wc_style_for_buttons        = $_csas_options->get_use_wc_style_for_buttons();

	// Limit settings for saved carts.
	//
	$_guest_cart_limit = $_csas_options->get_guest_cart_limit();
	$_user_cart_limit  = $_csas_options->get_user_cart_limit();

	$_checkboxes = array(
		array( 'allow_saving', $_allow_saving_checked, _x( 'Allow cart saving', 'options', 'cart-sharing-and-saving-for-woocommerce' ), null, false ),
		array( 'allow_sharing', $_allow_sharing_checked, _x( 'Allow sharing', 'options', 'cart-sharing-and-saving-for-woocommerce' ), null, false ),
		array( 'allow_guests_ops', $_allow_guests_save_carts_checked, _x( 'Allow guests to save carts', 'options', 'cart-sharing-and-saving-for-woocommerce' ), null, false ),
		array( 'share_coupons', $_share_applied_coupons, _x( 'Share applied coupons', 'options', 'cart-sharing-and-saving-for-woocommerce' ), null, false ),
		array( 'use_wc_style_for_buttons', $_use_wc_style_for_buttons, _x( 'Use WC styles for buttons', 'options', 'cart-sharing-and-saving-for-woocommerce' ), null, false ),
	);

	// Creating buttons' text fields.
	//
	$_button_names = $_csas_options->get_available_button_names();

	$_button_text_options = array();
	foreach ( $_button_names as $key => $name ) {
		$_lowercased_name       = strtolower( $name );
		$_button_text_options[] =
						array(
							$key . '_button_text',
							$_csas_options->get_button_text( $_lowercased_name ),
							$name,
							_x( 'Set button\'s text.', 'options', 'cart-sharing-and-saving-for-woocommerce' ),
							'',
							false,
						);
	}

	// Creating radio buttons for cart page visibility.
	//
	$_cart_page_visibility         = $_csas_options->get_cart_page_visibility();
	$_mini_cart_visibility         = $_csas_options->get_mini_cart_visibility();
	$_cart_page_visibility_options = $_csas_options->get_visibility_locations();

	// Creating radio buttons for checkout page visibility.
	//
	$_checkout_page_visibility         = $_csas_options->get_checkout_page_visibility();
	$_checkout_page_visibility_options = $_csas_options->get_visibility_locations();

	// options without language.
	//
	$_nonlanged_options = array(
		'allow_sharing',
		'allow_saving',
		'allow_guests_ops',
		'share_coupons',
		'use_wc_style_for_buttons',
		'cart_page_visibility',
		'checkout_page_visibility',
		'mini_cart_visibility',
		'is_enabled',
	);

	// Cart expiration.
	//
	$_cart_expiration = $_csas_options->get_cart_expiration();

	$_allowed_html = get_allowed_html();

	?>
	<div class="wrap csas-settings-wrap">
		<h1><?php echo esc_html( $_title ); ?></h1>
		<h2><?php echo esc_html( $_description ); ?></h2>
		<div class='general-info'><p><?php echo esc_html__( 'Plugin Version', 'cart-sharing-and-saving-for-woocommerce' ) . ': ' . esc_html( $_plugin_version ); ?> // Developed By Earlybirds</p></div>

		<form method="post" action="options.php">
			<?php settings_fields( 'ebcsas-settings-group' ); ?>
			<?php do_settings_sections( 'ebcsas-settings-group' ); ?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Enabled', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
					</th>
					<td>
						<label class='visually-hidden' for='is_enabled'><?php echo esc_html_x( 'Enabled', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?></label>
						<?php echo wp_kses( create_checkbox( 'is_enabled', $_is_enabled_val, null, false ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Saved Carts page', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
							<?php echo wp_kses( $_options_suffix, $_allowed_html ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( "Select the page to display the visitors' saved carts. It is required. The plugin does not automatically creates a Saved Carts Page in order to not meddle. Use the shortcode to show the cart.", 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<div class="csas-settings-line" style="flex-direction: column;">
							<select class="wc-page-search csas-searchbox" style="width: 50%;" name="<?php echo esc_html( $_options_prefix ); ?>[saved_carts_page_id]<?php echo esc_html( $_options_suffix ); ?>"; data-placeholder="<?php esc_attr_e( 'Select a page', 'cart-sharing-and-saving-for-woocommerce' ); ?>" data-action="woocommerce_json_search_pages">
								<?php
									$_saved_carts_page_id = $_csas_options->get_saved_carts_page_id();

								if ( ! empty( $_saved_carts_page_id ) ) {
									$_formatted_name = get_the_title( $_saved_carts_page_id );
									?>
										<option value="<?php echo esc_attr( $_saved_carts_page_id ); ?>" selected="selected"><?php echo esc_html( $_formatted_name ); ?></option>
										<?php
								}
								?>
							</select>
							<p><?php echo esc_html_x( 'Please use the shortcode', 'options', 'cart-sharing-and-saving-for-woocommerce' ) . ' ' . sprintf( '%s', '[display_saved_carts]' ) . ' ' . esc_html_x( 'in the target page in order to display the saved carts. Also, please exclude this page from page cache.', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?></p>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Share Cart page', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
							<?php echo wp_kses( $_options_suffix, $_allowed_html ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Select the page to display a shared cart. It is required. The plugin does not automatically creates a Saved Carts Page in order to not meddle. Use the shortcode to show the cart.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<div class="csas-settings-line" style="flex-direction: column;">
							<select class="wc-page-search csas-searchbox" style="width: 50%;" name="<?php echo esc_html( $_options_prefix ); ?>[share_cart_page_id]<?php echo esc_html( $_options_suffix ); ?>" data-placeholder="<?php esc_attr_e( 'Select a page', 'cart-sharing-and-saving-for-woocommerce' ); ?>" data-action="woocommerce_json_search_pages">
									<?php
										$_share_cart_page_id = $_csas_options->get_share_cart_page_id( $_lang );

									if ( ! empty( $_share_cart_page_id ) ) {
										$_formatted_name = get_the_title( $_share_cart_page_id );
										?>
											<option value="<?php echo esc_attr( $_share_cart_page_id ); ?>" selected="selected"><?php echo esc_html( $_formatted_name ); ?></option>
											<?php
									}
									?>
							</select>
							<p>
							<?php echo esc_html_x( 'Please use the shortcode', 'options', 'cart-sharing-and-saving-for-woocommerce' ) . ' ' . sprintf( '%s', '[display_shared_carts]' ) . ' ' . esc_html_x( 'in the target page in order to display the saved carts. Also, please exclude this page from page cache.', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?></p>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php echo esc_html_x( 'Options', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?></label></th>
					<td>
						<div>
							<?php
							foreach ( $_checkboxes as $checkbox_params ) {
								$_html  = '<div class="csas-settings-line">';
								$_html .= create_checkbox_option_html( ...$checkbox_params );
								$_html .= '</div>';

								echo wp_kses( $_html, $_allowed_html );
							}
							?>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php echo esc_html_x( 'Button Texts', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?></label></th>
					<td>
						<div>
							<?php
							foreach ( $_button_text_options as $text_option_params ) {
								$_html  = '<div class="csas-settings-line">';
								$_html .= create_text_option_html( ...$text_option_params );
								$_html .= '</div>';

								echo wp_kses( $_html, $_allowed_html );
							}
							?>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Limit of saved carts for guests', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php
						echo wp_kses( wc_help_tip( _x( 'Set the maximum number of carts that can be saved by guests.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html );
						?>
					</th>
					<td>
						<?php echo wp_kses( create_number_option( 'guest_cart_limit', $_guest_cart_limit, null, false ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Limit of saved carts for logged-in users', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Set the maximum number of carts that can be saved by logged-in users.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_number_option( 'user_cart_limit', $_user_cart_limit, null, false ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart expiration in hours', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Set how many hours pass before saved cart is expired. Use 0 for no expiration.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_number_option( 'cart_expiration', $_cart_expiration, null, false ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Limit reached for guests', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Message displayed when guests reach the saved cart limit.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php
						echo wp_kses( create_textarea_option_v2( 'guest_cart_limit_reached_message', 'guest_cart_limit_reached_message' ), $_allowed_html );
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Limit reached for logged-in users', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Message displayed when logged-in users reach the saved cart limit.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'user_cart_limit_reached_message', 'user_cart_limit_reached_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Guest not allowed to save', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Message displayed when guests try to save when not allowed.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'guest_not_allowed_to_save_message', 'guest_not_allowed_to_save_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart saved message', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'A message the user will see after a successful cart saving.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'cart_saved_message', 'cart_saved_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart shared message', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'The shared cart message.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'cart_shared_message', 'cart_shared_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart deleted message', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'A message the user will see after a cart has been successfully deleted.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'cart_deleted_message', 'cart_deleted_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart applied message', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'A message the user will see after a cart has been successfully applied and loaded into the current WC cart.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<?php echo wp_kses( create_textarea_option_v2( 'cart_applied_message', 'cart_applied_message' ), $_allowed_html ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Cart page visibility', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Choose where the buttons will be visible on the cart page.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<div>
							<?php
								$_html  = '<div class="csas-settings-line non-justified">';
								$_html .= create_radiobox_option( 'cart_page_visibility', $_cart_page_visibility, $_cart_page_visibility_options, null, false );
								$_html .= '</div>';
								$_html .= '<p>' . _x( "Will only work for classic WC cart widget. Won't work for the blocks cart widget. For WC blocks cart widget, use the editor to add saving and sharing buttons using [csas_share_button] and [csas_save_button].", 'options', 'cart-sharing-and-saving-for-woocommerce' ) . '</p>';
								echo wp_kses( $_html, $_allowed_html );
							?>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Checkout page Visibility', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Choose where the buttons will be visible on the checkout page.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<div>
							<?php
								$_html  = '<div class="csas-settings-line non-justified">';
								$_html .= create_radiobox_option( 'checkout_page_visibility', $_checkout_page_visibility, $_checkout_page_visibility_options, null, false );
								$_html .= '</div>';
								$_html .= '<p>' . _x( "Will only work for classic WC checkout widget. Won't work for the blocks checkout widget. For WC blocks checkout widget, use the editor to add saving and sharing buttons using [csas_share_button] and [csas_save_button].", 'options', 'cart-sharing-and-saving-for-woocommerce' ) . '</p>';

								echo wp_kses( $_html, $_allowed_html );
							?>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>
							<?php echo esc_html_x( 'Mini cart visibility', 'options', 'cart-sharing-and-saving-for-woocommerce' ); ?>
						</label>
						<?php echo wp_kses( wc_help_tip( _x( 'Choose where the buttons will be visible in the mini cart frame.', 'options', 'cart-sharing-and-saving-for-woocommerce' ), true ), $_allowed_html ); ?>
					</th>
					<td>
						<div>
							<?php
								$_html  = '<div class="csas-settings-line non-justified">';
								$_html .= create_radiobox_option( 'mini_cart_visibility', $_mini_cart_visibility, $_checkout_page_visibility_options, null, false );
								$_html .= '</div>';
								$_html .= '<p>' . _x( 'Will only work for widgets that use WC native woocommerce_widget_shopping_cart_after/before_buttons hooks.', 'options', 'cart-sharing-and-saving-for-woocommerce' ) . '</p>';
								echo wp_kses( $_html, $_allowed_html );
							?>
						</div>
					</td>
				</tr>

			</table>

			<?php submit_button(); ?>

		</form>
	</div>
	<?php
}

/**
 * Additional actions and hooks when options are saved.
 * In this case, flushes rewrite rules.
 *
 * @return void
 */
function handle_submission() {

	if (
		( sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ) === 'update' )
		&& ( sanitize_text_field( wp_unslash( $_POST['option_page'] ?? '' ) ) === 'ebcsas-settings-group' )
	) {
		check_admin_referer( 'ebcsas-settings-group-options' );

		wp_cache_delete( 'rewrite_rules', 'options' );
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );

		\EB\CSAS\Cache\flush_carts_page_cache();
	}
}
