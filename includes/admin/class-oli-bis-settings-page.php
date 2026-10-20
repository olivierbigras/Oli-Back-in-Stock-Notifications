<?php
/**
 * WooCommerce settings tab.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds WooCommerce > Settings > Stock Notifications.
 *
 * @since 0.1.0
 */
class Oli_BIS_Settings_Page {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 60 );
		add_action( 'woocommerce_settings_oli_bis', array( $this, 'render' ) );
		add_action( 'woocommerce_settings_save_oli_bis', array( $this, 'save' ) );
	}

	/**
	 * Adds the settings tab.
	 *
	 * @since 0.1.0
	 * @param array<string,string> $tabs Tabs.
	 * @return array<string,string>
	 */
	public function add_tab( $tabs ) {
		$tabs['oli_bis'] = __( 'Stock Notifications', 'oli-back-in-stock-notifications' );
		return $tabs;
	}

	/**
	 * Settings fields.
	 *
	 * @since 0.1.0
	 * @return array<int,array<string,mixed>>
	 */
	public function fields() {
		$settings = Oli_BIS_Settings::all();

		return array(
			array(
				'title' => __( 'General', 'oli-back-in-stock-notifications' ),
				'type'  => 'title',
				'id'    => 'oli_bis_general',
			),
			array(
				'title'   => __( 'Require double opt-in', 'oli-back-in-stock-notifications' ),
				'desc'    => __( 'Customers must confirm their email before the signup becomes active.', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_double_optin',
				'type'    => 'checkbox',
				'default' => $settings['double_optin'],
				'value'   => $settings['double_optin'],
			),
			array(
				'title'             => __( 'Delete unverified signups after (days)', 'oli-back-in-stock-notifications' ),
				'id'                => 'oli_bis_delete_unverified_days',
				'type'              => 'number',
				'custom_attributes' => array(
					'min' => 1,
				),
				'default'           => $settings['delete_unverified_days'],
				'value'             => $settings['delete_unverified_days'],
			),
			array(
				'title'   => __( 'Require account to sign up', 'oli-back-in-stock-notifications' ),
				'desc'    => __( 'Guests are asked to log in before signing up.', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_require_account',
				'type'    => 'checkbox',
				'default' => $settings['require_account'],
				'value'   => $settings['require_account'],
			),
			array(
				'title'   => __( 'Allow signups on backorder', 'oli-back-in-stock-notifications' ),
				'desc'    => __( 'Show the form on products that are available on backorder.', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_allow_backorder',
				'type'    => 'checkbox',
				'default' => $settings['allow_backorder'],
				'value'   => $settings['allow_backorder'],
			),
			array(
				'title'             => __( 'Minimum stock quantity', 'oli-back-in-stock-notifications' ),
				'desc'              => __( 'Wait until stock reaches this quantity before sending notifications. Use 0 to send as soon as the product is in stock.', 'oli-back-in-stock-notifications' ),
				'id'                => 'oli_bis_min_stock',
				'type'              => 'number',
				'custom_attributes' => array(
					'min' => 0,
				),
				'default'           => $settings['min_stock'],
				'value'             => $settings['min_stock'],
			),
			array(
				'title'   => __( 'Email admin on new signups', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_admin_signup_email',
				'type'    => 'checkbox',
				'default' => $settings['admin_signup_email'],
				'value'   => $settings['admin_signup_email'],
			),
			array(
				'title'             => __( 'Email batch size', 'oli-back-in-stock-notifications' ),
				'id'                => 'oli_bis_batch_size',
				'type'              => 'number',
				'custom_attributes' => array(
					'min' => 1,
					'max' => 200,
				),
				'default'           => $settings['batch_size'],
				'value'             => $settings['batch_size'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_bis_general',
			),
			array(
				'title' => __( 'Product page', 'oli-back-in-stock-notifications' ),
				'type'  => 'title',
				'id'    => 'oli_bis_product_page',
			),
			array(
				'title'   => __( 'Display opt-in checkbox', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_gdpr_checkbox',
				'type'    => 'checkbox',
				'default' => $settings['gdpr_checkbox'],
				'value'   => $settings['gdpr_checkbox'],
			),
			array(
				'title'   => __( 'Opt-in checkbox text', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_gdpr_text',
				'type'    => 'text',
				'css'     => 'min-width:400px;',
				'default' => $settings['gdpr_text'],
				'value'   => $settings['gdpr_text'],
			),
			array(
				'title'   => __( 'Display signed-up customers', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_show_count',
				'type'    => 'checkbox',
				'default' => $settings['show_count'],
				'value'   => $settings['show_count'],
			),
			array(
				'title'   => __( 'Sign-up form text', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_form_text',
				'type'    => 'text',
				'css'     => 'min-width:400px;',
				'default' => $settings['form_text'],
				'value'   => $settings['form_text'],
			),
			array(
				'title'   => __( 'Already signed-up text', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_form_text_signed_up',
				'type'    => 'text',
				'css'     => 'min-width:400px;',
				'default' => $settings['form_text_signed_up'],
				'value'   => $settings['form_text_signed_up'],
			),
			array(
				'title'   => __( 'Button text', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_button_text',
				'type'    => 'text',
				'default' => $settings['button_text'],
				'value'   => $settings['button_text'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_bis_product_page',
			),
			array(
				'title' => __( 'Catalog', 'oli-back-in-stock-notifications' ),
				'type'  => 'title',
				'id'    => 'oli_bis_catalog',
			),
			array(
				'title'   => __( 'Display sign-up prompt in catalog', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_catalog_prompt',
				'type'    => 'checkbox',
				'default' => $settings['catalog_prompt'],
				'value'   => $settings['catalog_prompt'],
			),
			array(
				'title'   => __( 'Catalog prompt text', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_catalog_text',
				'type'    => 'text',
				'css'     => 'min-width:400px;',
				'default' => $settings['catalog_text'],
				'value'   => $settings['catalog_text'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_bis_catalog',
			),
			array(
				'title' => __( 'Email templates', 'oli-back-in-stock-notifications' ),
				'desc'  => __( 'Optional bodies imported from the PixM plugin. Leave empty to use the default WooCommerce email content. Placeholders: {product_name}, {product_url}, {site_name}.', 'oli-back-in-stock-notifications' ),
				'type'  => 'title',
				'id'    => 'oli_bis_email_templates',
			),
			array(
				'title'   => __( 'Signup confirmation body', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_signup_body',
				'type'    => 'textarea',
				'css'     => 'min-width:400px; min-height:120px;',
				'default' => $settings['signup_body'],
				'value'   => $settings['signup_body'],
			),
			array(
				'title'   => __( 'Back-in-stock email body', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_notification_body',
				'type'    => 'textarea',
				'css'     => 'min-width:400px; min-height:120px;',
				'default' => $settings['notification_body'],
				'value'   => $settings['notification_body'],
			),
			array(
				'title'   => __( 'Auto-cleanup sent notifications', 'oli-back-in-stock-notifications' ),
				'desc'    => __( 'Delete notified subscriptions after the number of days below (PixM behaviour).', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_auto_cleanup_notified',
				'type'    => 'checkbox',
				'default' => $settings['auto_cleanup_notified'],
				'value'   => $settings['auto_cleanup_notified'],
			),
			array(
				'title'             => __( 'Delete sent notifications after (days)', 'oli-back-in-stock-notifications' ),
				'id'                => 'oli_bis_cleanup_notified_days',
				'type'              => 'number',
				'custom_attributes' => array(
					'min' => 1,
				),
				'default'           => $settings['cleanup_notified_days'],
				'value'             => $settings['cleanup_notified_days'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_bis_email_templates',
			),
			array(
				'title' => __( 'Data', 'oli-back-in-stock-notifications' ),
				'type'  => 'title',
				'id'    => 'oli_bis_data',
			),
			array(
				'title'   => __( 'Delete data on uninstall', 'oli-back-in-stock-notifications' ),
				'desc'    => __( 'Remove the subscriber tables and settings when the plugin is deleted.', 'oli-back-in-stock-notifications' ),
				'id'      => 'oli_bis_delete_data_on_uninstall',
				'type'    => 'checkbox',
				'default' => $settings['delete_data_on_uninstall'],
				'value'   => $settings['delete_data_on_uninstall'],
			),
			array(
				'type' => 'sectionend',
				'id'   => 'oli_bis_data',
			),
		);
	}

	/**
	 * Renders settings fields.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render() {
		woocommerce_admin_fields( $this->fields() );
	}

	/**
	 * Saves settings from the WC tab.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'woocommerce-settings' );

		$input = array(
			'double_optin'             => ! empty( $_POST['oli_bis_double_optin'] ) ? 'yes' : 'no',
			'delete_unverified_days'   => isset( $_POST['oli_bis_delete_unverified_days'] ) ? absint( wp_unslash( $_POST['oli_bis_delete_unverified_days'] ) ) : 7,
			'require_account'          => ! empty( $_POST['oli_bis_require_account'] ) ? 'yes' : 'no',
			'allow_backorder'          => ! empty( $_POST['oli_bis_allow_backorder'] ) ? 'yes' : 'no',
			'min_stock'                => isset( $_POST['oli_bis_min_stock'] ) ? absint( wp_unslash( $_POST['oli_bis_min_stock'] ) ) : 0,
			'admin_signup_email'       => ! empty( $_POST['oli_bis_admin_signup_email'] ) ? 'yes' : 'no',
			'batch_size'               => isset( $_POST['oli_bis_batch_size'] ) ? absint( wp_unslash( $_POST['oli_bis_batch_size'] ) ) : 50,
			'gdpr_checkbox'            => ! empty( $_POST['oli_bis_gdpr_checkbox'] ) ? 'yes' : 'no',
			'gdpr_text'                => isset( $_POST['oli_bis_gdpr_text'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_gdpr_text'] ) ) : '',
			'show_count'               => ! empty( $_POST['oli_bis_show_count'] ) ? 'yes' : 'no',
			'form_text'                => isset( $_POST['oli_bis_form_text'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_form_text'] ) ) : '',
			'form_text_signed_up'      => isset( $_POST['oli_bis_form_text_signed_up'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_form_text_signed_up'] ) ) : '',
			'button_text'              => isset( $_POST['oli_bis_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_button_text'] ) ) : '',
			'catalog_prompt'           => ! empty( $_POST['oli_bis_catalog_prompt'] ) ? 'yes' : 'no',
			'catalog_text'             => isset( $_POST['oli_bis_catalog_text'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_catalog_text'] ) ) : '',
			'delete_data_on_uninstall' => ! empty( $_POST['oli_bis_delete_data_on_uninstall'] ) ? 'yes' : 'no',
			'auto_cleanup_notified'    => ! empty( $_POST['oli_bis_auto_cleanup_notified'] ) ? 'yes' : 'no',
			'cleanup_notified_days'    => isset( $_POST['oli_bis_cleanup_notified_days'] ) ? absint( wp_unslash( $_POST['oli_bis_cleanup_notified_days'] ) ) : 30,
			'notification_body'        => isset( $_POST['oli_bis_notification_body'] ) ? wp_kses_post( wp_unslash( $_POST['oli_bis_notification_body'] ) ) : '',
			'signup_body'              => isset( $_POST['oli_bis_signup_body'] ) ? wp_kses_post( wp_unslash( $_POST['oli_bis_signup_body'] ) ) : '',
		);

		Oli_BIS_Settings::update( $input );
	}
}
