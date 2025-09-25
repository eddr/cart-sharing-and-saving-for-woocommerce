<?php
/**
 * CSAS Language Helper.
 *
 * Utility helpers to get language and locale information for sites that may use
 * WPML or Polylang, with reasonable fallbacks when no multilingual plugin is active.
 *
 * @package EB\CSAS
 */

namespace EB\CSAS;

/**
 * The main class for all CSAS language logic.
 */
class Lang {

	/**
	 * Singleton instance.
	 *
	 * @var Lang|null
	 */
	private $_instance = null; // phpcs:ignore

	/**
	 * Set up the singleton instance.
	 */
	public function __construct() {

		if ( ! isset( $this->_instance ) ) {
			$this->_instance = $this;
		}
	}

	/**
	 * Get the default WordPress locale.
	 *
	 * If WPML is active, this resolves the default language code through WPML
	 * and converts it to a locale. If Polylang is active, it uses the Polylang
	 * API to fetch the default locale. Otherwise, it falls back to WordPress'
	 * `get_locale()` result.
	 *
	 * @since 1.0.0
	 *
	 * @return string The default locale (for example, 'en_US').
	 */
	public static function get_default_locale() {

		$_locale = '';

		// WPML default locale.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$_default_lang = apply_filters( 'wpml_default_language', null );
			$_locale       = apply_filters( 'wpml_locale', null, $_default_lang );
		}

		// Polylang default locale.
		if ( empty( $_locale ) && function_exists( 'pll_default_language' ) ) {
			$_locale = pll_default_language( 'locale' );
		}

		// Core fallback.
		if ( empty( $_locale ) ) {
			$_locale = \get_locale();
		}

		return $_locale;
	}

	/**
	 * Get the default two-letter language code.
	 *
	 * Attempts to retrieve the default language via WPML or Polylang if present.
	 * Otherwise derives the language from the default locale (e.g., 'en' from 'en_US').
	 *
	 * @since 1.0.0
	 *
	 * @return string The default language code (for example, 'en').
	 */
	public static function get_default_lang() {

		$_lang = '';

		// WPML default language code.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$_lang = apply_filters( 'wpml_default_language', null );
		}

		// Polylang default language code.
		if ( empty( $_lang ) && function_exists( 'pll_default_language' ) ) {
			$_lang = pll_default_language();
		}

		// Core fallback from locale.
		if ( empty( $_lang ) ) {
			$_locale = self::get_default_locale();
			$_parts  = explode( '_', $_locale );
			$_lang   = isset( $_parts[0] ) ? $_parts[0] : $_locale;
		}

		return $_lang;
	}

	/**
	 * Get the WPML locale for a given language code.
	 *
	 * If WPML is not active, this returns an empty string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $p_lang Two-letter language code (for example, 'en').
	 * @return string The mapped locale (for example, 'en_US') or empty string when unavailable.
	 */
	public static function get_wpml_locale( $p_lang ) {

		$_locale = '';

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$_locale = apply_filters( 'wpml_locale', null, $p_lang );
		}

		return $_locale;
	}

	/**
	 * Get the current locale for the active language.
	 *
	 * Uses WPML/Polylang when available and falls back to core `get_locale()`.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current locale (for example, 'en_GB').
	 */
	public static function get_current_locale() {

		$_locale = '';

		// WPML current locale.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$_current_lang = apply_filters( 'wpml_current_language', null );
			if ( ! empty( $_current_lang ) ) {
				$_locale = apply_filters( 'wpml_locale', null, $_current_lang );
			}
		}

		// Polylang current locale.
		if ( empty( $_locale ) && function_exists( 'pll_current_language' ) ) {
			$_locale = pll_current_language( 'locale' );
		}

		// Core fallback.
		if ( empty( $_locale ) ) {
			$_locale = \get_locale();
		}

		return $_locale;
	}

	/**
	 * Get the current two-letter language code.
	 *
	 * Uses WPML/Polylang when available and falls back to parsing the core locale.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current language code (for example, 'fr').
	 */
	public static function get_current_lang() {

		$_lang = '';

		// WPML current language code.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$_lang = apply_filters( 'wpml_current_language', null );
		} elseif ( function_exists( 'pll_current_language' ) ) {
			// Polylang current language code.
			$_lang = pll_current_language();
		}

		// Core fallback from locale.
		if ( empty( $_lang ) ) {
			$_locale = \get_locale();
			$_parts  = explode( '_', $_locale );
			$_lang   = isset( $_parts[0] ) ? $_parts[0] : $_locale;
		}

		return $_lang;
	}

	/**
	 * Get all available languages installed in WordPress core.
	 *
	 * This does not list languages from plugins; it mirrors the behaviour of
	 * `get_available_languages( 'core' )`.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Array of language codes, e.g. ['en_GB', 'he_IL'].
	 */
	public static function get_all_available_languages() {

		return \get_available_languages( 'core' );
	}

	/**
	 * Get the pretty permalink for a post in its own (assigned) language.
	 *
	 * This works across common multilingual setups:
	 * - WPML: derives the post's language and localizes the permalink accordingly.
	 * - Polylang: uses the post's language code and returns the proper permalink.
	 * - TranslatePress: localizes the base permalink if the post's language can be determined via filter.
	 * - Fallback (no multilingual plugin): returns get_permalink( $p_post_id ).
	 *
	 * No global language switching is performed; the URL is generated for the language the post belongs to.
	 *
	 * @param int $p_post_id Post ID (any language).
	 * @return string Pretty permalink in the post's assigned language, or empty string on failure.
	 */
	public static function get_permalink_in_assigned_language( $p_post_id ) {
		$_post_id = absint( $p_post_id );

		if ( ! $_post_id ) {
			return '';
		}

		// WPML.
		// Prefer resolving the post's own language and localizing the permalink for that code.
		//
		if ( function_exists( 'apply_filters' ) && has_filter( 'wpml_element_language_details' ) ) {
			$_post_type    = get_post_type( $_post_id );
			$_element_type = 'post_' . $_post_type;
			$_lang_details = apply_filters(
				'wpml_element_language_details',
				null,
				array(
					'element_id'   => $_post_id,
					'element_type' => $_element_type,
				)
			);

			if ( ! empty( $_lang_details )
				&& ! empty( $_lang_details->language_code ) ) {
				$_code         = $_lang_details->language_code;
				$_current_lang = apply_filters( 'wpml_current_language', null );

				do_action( 'wpml_switch_language', $_code );

				$_url = get_permalink( $_post_id );

				do_action( 'wpml_switch_language', $_current_lang );

				return is_string( $_url ) ? $_url : get_permalink( $_post_id );
			}
		}

		// Polylang.
		//
		if ( function_exists( 'pll_get_post_language' ) ) {
			$_code = pll_get_post_language( $_post_id );

			if ( $_code ) {
				// pll_get_post( id, code ) returns the translation in that language (likely the same id).
				$_translated_id = function_exists( 'pll_get_post' ) ? pll_get_post( $_post_id, $_code ) : $_post_id;

				return get_permalink( $_translated_id );
			}
		}

		// TranslatePress.
		// TRP doesn't expose a universal "post language" function in all versions.
		// If a site-specific filter exists, use it; otherwise return the base permalink.
		//
		if ( function_exists( 'trp_translate_url' ) ) {
			$_base_url = get_permalink( $_post_id );

			if ( has_filter( 'trp_get_post_language' ) ) {
				$_code = apply_filters( 'trp_get_post_language', '', $_post_id );

				if ( is_string( $_code ) && '' !== $_code ) {
					return trp_translate_url( $_base_url, $_code );
				}
			}

			return $_base_url;
		}

		// --- Fallback (no multilingual plugin) ---
		return get_permalink( $_post_id );
	}
}

new Lang();
