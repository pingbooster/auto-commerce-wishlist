<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PB_Wishlist_Theme_Color_Detector
 *
 * Tries multiple strategies (in order of reliability) to figure out
 * the active theme's brand/accent color, without needing to know the
 * theme in advance. Falls back to a sensible neutral if nothing is found.
 */
class PB_Wishlist_Theme_Color_Detector {

	/**
	 * Common theme_mod / option keys that themes typically use
	 * for their primary brand color. Covers most custom + page-builder themes.
	 */
	private static $common_keys = array(
		'primary_color',
		'accent_color',
		'link_color',
		'theme_color',
		'brand_color',
		'button_color',
		'button_background_color',
		'main_color',
		'highlight_color',
		'primary_colour',
		'accent_colour',
		'brand_colour',
		// Common custom-theme-options patterns (e.g. "PB Jewelry Woo Theme" style panels):
		'pb_primary_color',
		'pb_accent_color',
		'theme_options_primary_color',
	);

	/**
	 * Get the detected accent color (hex string), cached for 12 hours.
	 */
	public static function get_accent_color() {

		$cached = get_transient( 'auto_commerce_wishlist_theme_accent_color' );
		if ( false !== $cached ) {
			return $cached;
		}

		$color = self::detect_from_block_theme_json();

		if ( ! $color ) {
			$color = self::detect_from_theme_mods();
		}

		if ( ! $color ) {
			$color = self::detect_from_custom_options_table();
		}

		if ( ! $color ) {
			$color = self::detect_from_stylesheet();
		}

		if ( ! $color ) {
			// Safe, neutral fallback — a warm gold, fitting for jewelry stores,
			// but only used if literally nothing else could be detected.
			$color = '#b8860b';
		}

		set_transient( 'auto_commerce_wishlist_theme_accent_color', $color, 12 * HOUR_IN_SECONDS );

		return $color;
	}

	/**
	 * Strategy 1: Block themes (theme.json) expose an official color palette.
	 * This is the most reliable source when available.
	 */
	private static function detect_from_block_theme_json() {

		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return false;
		}

		$settings = wp_get_global_settings( array( 'color', 'palette' ) );

		if ( empty( $settings['theme'] ) || ! is_array( $settings['theme'] ) ) {
			return false;
		}

		foreach ( $settings['theme'] as $swatch ) {
			$slug = isset( $swatch['slug'] ) ? strtolower( $swatch['slug'] ) : '';
			if ( isset( $swatch['color'] ) && preg_match( '/primary|accent|brand/', $slug ) ) {
				return sanitize_hex_color( $swatch['color'] );
			}
		}

		// If no slug matched, just take the first defined color as a best guess.
		if ( isset( $settings['theme'][0]['color'] ) ) {
			return sanitize_hex_color( $settings['theme'][0]['color'] );
		}

		return false;
	}

	/**
	 * Strategy 2: Classic themes register colors via the Customizer (theme_mods).
	 */
	private static function detect_from_theme_mods() {

		$mods = get_theme_mods();
		if ( empty( $mods ) || ! is_array( $mods ) ) {
			return false;
		}

		// First pass: exact matches against our known common keys.
		foreach ( self::$common_keys as $key ) {
			if ( ! empty( $mods[ $key ] ) && self::looks_like_color( $mods[ $key ] ) ) {
				return sanitize_hex_color( $mods[ $key ] );
			}
		}

		// Second pass: any mod key that merely contains "color"/"colour".
		foreach ( $mods as $key => $value ) {
			if ( is_string( $value ) && preg_match( '/color|colour/i', $key ) && self::looks_like_color( $value ) ) {
				return sanitize_hex_color( $value );
			}
		}

		return false;
	}

	/**
	 * Strategy 3: Many custom-built themes (like bespoke "Theme Options" panels,
	 * e.g. a "PB Jewelry Woo Theme" style admin page) store settings as a single
	 * serialized array in wp_options rather than as individual theme_mods.
	 * We scan option names that look theme-option-ish for a color-like value.
	 */
	private static function detect_from_custom_options_table() {
		global $wpdb;

		$like = $wpdb->esc_like( 'theme_option' ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 20",
				$like
			)
		);

		if ( empty( $rows ) ) {
			return false;
		}

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->option_value );
			if ( ! is_array( $data ) ) {
				continue;
			}
			foreach ( $data as $key => $value ) {
				if ( is_string( $value ) && preg_match( '/color|colour/i', (string) $key ) && self::looks_like_color( $value ) ) {
					return sanitize_hex_color( $value );
				}
			}
		}

		return false;
	}

	/**
	 * Strategy 4: Last resort — scan the active theme's main stylesheet for
	 * CSS custom properties (--primary-color, --accent, etc.) which many
	 * modern themes define in :root.
	 */
	private static function detect_from_stylesheet() {

		$style_path = get_stylesheet_directory() . '/style.css';

		if ( ! file_exists( $style_path ) || ! is_readable( $style_path ) ) {
			return false;
		}

		// Only read the first 20kb — brand variables are almost always declared near the top.
		$handle = fopen( $style_path, 'r' );
		if ( ! $handle ) {
			return false;
		}
		$css = fread( $handle, 20000 );
		fclose( $handle );

		if ( preg_match( '/--(?:[\w-]*)(?:primary|accent|brand)(?:[\w-]*)\s*:\s*(#[0-9a-fA-F]{3,6})/', $css, $matches ) ) {
			return sanitize_hex_color( $matches[1] );
		}

		return false;
	}

	/**
	 * Loose check for whether a string is a usable hex color.
	 */
	private static function looks_like_color( $value ) {
		return is_string( $value ) && preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim( $value ) );
	}
}
