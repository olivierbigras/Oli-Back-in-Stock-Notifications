<?php
/**
 * Language helpers for TranslatePress and locales.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects and switches subscriber language when sending mail.
 *
 * @since 0.1.0
 */
class Oli_BIS_Language {

	/**
	 * Current request language/locale.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function current() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		if ( ! empty( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ) {
			$locale = self::normalize( $GLOBALS['TRP_LANGUAGE'] );
		}

		/**
		 * Filters the language stored with a new signup.
		 *
		 * @since 0.1.0
		 * @param string $locale Locale.
		 */
		return sanitize_text_field( apply_filters( 'oli_bis_current_language', $locale ) );
	}

	/**
	 * Switches WordPress and TranslatePress to a subscriber locale.
	 *
	 * @since 0.1.0
	 * @param string $locale Locale.
	 * @return string Previous locale.
	 */
	public static function switch_to( $locale ) {
		$previous = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale   = self::normalize( $locale );

		if ( '' === $locale ) {
			return $previous;
		}

		if ( ! empty( $GLOBALS['TRP_LANGUAGE'] ) ) {
			$GLOBALS['oli_bis_prev_trp_language'] = $GLOBALS['TRP_LANGUAGE'];
			$GLOBALS['TRP_LANGUAGE']              = $locale;
		}

		if ( function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( $locale );
		}

		/**
		 * Fires after the plugin switches language for an email.
		 *
		 * @since 0.1.0
		 * @param string $locale   Target locale.
		 * @param string $previous Previous locale.
		 */
		do_action( 'oli_bis_switch_language', $locale, $previous );

		return $previous;
	}

	/**
	 * Restores the previous locale.
	 *
	 * @since 0.1.0
	 * @param string $previous Previous locale.
	 * @return void
	 */
	public static function restore( $previous ) {
		if ( isset( $GLOBALS['oli_bis_prev_trp_language'] ) ) {
			$GLOBALS['TRP_LANGUAGE'] = $GLOBALS['oli_bis_prev_trp_language'];
			unset( $GLOBALS['oli_bis_prev_trp_language'] );
		}

		if ( function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		} elseif ( $previous && function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( $previous );
		}

		/**
		 * Fires after language is restored.
		 *
		 * @since 0.1.0
		 * @param string $previous Previous locale.
		 */
		do_action( 'oli_bis_restore_language', $previous );
	}

	/**
	 * Normalizes a locale or language code.
	 *
	 * @since 0.1.0
	 * @param string $locale Locale.
	 * @return string
	 */
	public static function normalize( $locale ) {
		$locale = str_replace( '-', '_', sanitize_text_field( (string) $locale ) );

		if ( preg_match( '/^[a-z]{2}$/', $locale ) ) {
			$map = array(
				'en' => 'en_US',
				'fr' => 'fr_FR',
			);

			return isset( $map[ $locale ] ) ? $map[ $locale ] : $locale;
		}

		return $locale;
	}
}
