<?php
/**
 * Optional custom email body from settings.
 *
 * @package Oli_BIS
 * @since   0.1.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replaces placeholders in stored email templates.
 *
 * @since 0.1.1
 */
class Oli_BIS_Email_Content {

	/**
	 * Replaces placeholders in a template.
	 *
	 * @since 0.1.1
	 * @param string               $template     Template.
	 * @param array<string,string> $placeholders Placeholder map including braces.
	 * @return string
	 */
	public static function replace( $template, array $placeholders ) {
		$template = is_string( $template ) ? $template : '';

		if ( '' === $template ) {
			return '';
		}

		$placeholders = self::with_site_aliases( $placeholders );

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
	}

	/**
	 * Treats {site_name} as an alias of {site_title}.
	 *
	 * @since 0.1.3
	 * @param array<string,string> $placeholders Placeholder map including braces.
	 * @return array<string,string>
	 */
	public static function with_site_aliases( array $placeholders ) {
		if ( array_key_exists( '{site_title}', $placeholders ) && ! array_key_exists( '{site_name}', $placeholders ) ) {
			$placeholders['{site_name}'] = $placeholders['{site_title}'];
		}

		if ( array_key_exists( '{site_name}', $placeholders ) && ! array_key_exists( '{site_title}', $placeholders ) ) {
			$placeholders['{site_title}'] = $placeholders['{site_name}'];
		}

		return $placeholders;
	}

	/**
	 * Returns a custom body if configured.
	 *
	 * @since 0.1.1
	 * @param string               $setting_key  Setting key.
	 * @param array<string,string> $placeholders Placeholders.
	 * @return string
	 */
	public static function custom_body( $setting_key, array $placeholders ) {
		return self::replace( (string) Oli_BIS_Settings::get( $setting_key, '' ), $placeholders );
	}
}
