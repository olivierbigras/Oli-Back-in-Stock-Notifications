=== Oli Back in Stock Notifications for WooCommerce ===
Contributors: bigrat95
Donate link: https://olivierbigras.com/
Tags: woocommerce, back in stock, stock notifications, waitlist, restock
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers sign up to be notified when out-of-stock WooCommerce products are available again.

== Description ==

Oli Back in Stock Notifications for WooCommerce adds a "Notify me" form to out-of-stock simple products and variations. Guests and logged-in customers can join a waitlist. When stock returns, the plugin sends batched WooCommerce emails.

= Highlights =

* Notify-me form on out-of-stock simple and variable products (per variation)
* Optional form on backorder products
* Guest and logged-in signups
* Optional GDPR consent checkbox
* Optional double opt-in
* Honeypot and rate limiting
* Admin subscription list with filter, search, CSV export, delete, and manual notify
* Per-product waitlist count column
* Automatic restock emails using WooCommerce email classes and customizable templates
* French and English strings
* Action Scheduler batching
* One-click unsubscribe links with signed tokens
* My Account tab: Stock notifications
* Signup confirmation email
* Optional admin email on new signups
* Dashboard stats: signups, notifications sent, and conversions via order attribution
* Shortcode `[oli_bis_notify_form]` (PixM alias `[wc_back_in_stock_form]`) and a block for the form
* Safe import from the PixM `wc-back-in-stock-notifications` table (dry-run, never deletes source data)
* Optional custom email bodies and auto-cleanup of sent notifications
* TranslatePress-aware emails (subscriber language)
* High-Performance Order Storage declared
* Classic and block product templates

This plugin does not phone home, track stores, or display upsells.

= Shortcode =

`[oli_bis_notify_form]` uses the current product.

`[oli_bis_notify_form product_id="123"]` targets a specific product.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/oli-back-in-stock-notifications`.
2. Activate the plugin through the Plugins screen.
3. Keep out-of-stock products visible under WooCommerce > Settings > Products > Inventory.
4. Review WooCommerce > Settings > Stock Notifications.
5. Customize emails under WooCommerce > Settings > Emails.

== Frequently Asked Questions ==

= Does this replace the official WooCommerce Back In Stock Notifications extension? =

It targets the same store-owner workflow: product-page signups, waitlist management, and batched restock emails. See the GitHub README for a gap list.

= Will it keep data from an existing waitlist plugin? =

Yes. Version 0.1.1 detects Olivier's PixM plugin table `wp_wc_back_in_stock_notifications` and the options `wc_bis_email_template`, `wc_bis_confirmation_email_template`, and `wc_bis_auto_cleanup`. Use WooCommerce → Stock dashboard → Dry-run import, then Import subscribers. The import is idempotent (email + product + variation, plus stored PixM row IDs). Source rows and options are never deleted.

= Can customers unsubscribe? =

Yes. Confirmation and restock emails include a signed one-click link. Logged-in customers can also manage requests from My Account > Stock notifications.

== Changelog ==

= 0.1.1 =
* PixM subscriber import (dry-run, idempotent, source data kept).
* Shortcode alias `[wc_back_in_stock_form]`.
* Custom email bodies and auto-cleanup of sent notifications.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.1.1 =
Adds a safe importer for the live PixM waitlist table so you can switch plugins without losing subscribers.

= 0.1.0 =
Initial public version.
