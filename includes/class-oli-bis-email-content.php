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

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
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
