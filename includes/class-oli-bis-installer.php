<?php
/**
 * Activation and schema installer.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates tables, options, and scheduled cleanup.
 *
 * @since 0.1.0
 */
class Oli_BIS_Installer {

	/**
	 * Runs on plugin activation.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function activate() {
		self::install();
		update_option( 'oli_bis_flush_rewrites', 'yes' );
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'oli_bis_cleanup_unverified' );
		flush_rewrite_rules();
	}

	/**
	 * Creates or updates database tables.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		dbDelta( Oli_BIS_Schema::subscriptions_sql( $prefix, $charset ) );
		dbDelta( Oli_BIS_Schema::stats_sql( $prefix, $charset ) );

		if ( false === get_option( Oli_BIS_Settings::OPTION_KEY, false ) ) {
			add_option( Oli_BIS_Settings::OPTION_KEY, Oli_BIS_Settings::defaults() );
		}

		update_option( 'oli_bis_db_version', Oli_BIS_Schema::DB_VERSION );

		if ( ! wp_next_scheduled( 'oli_bis_cleanup_unverified' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'oli_bis_cleanup_unverified' );
		}

		/**
		 * Fires after tables are installed so a legacy PixM plugin can migrate.
		 *
		 * @since 0.1.0
		 */
		do_action( 'oli_bis_installed' );
	}

	/**
	 * Maybe upgrades the schema when versions differ.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'oli_bis_db_version', '' );

		if ( Oli_BIS_Schema::DB_VERSION !== $installed ) {
			self::install();
		}

		if ( 'yes' === get_option( 'oli_bis_flush_rewrites', '' ) ) {
			flush_rewrite_rules();
			delete_option( 'oli_bis_flush_rewrites' );
		}
	}
}
