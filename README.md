# Oli Back in Stock Notifications for WooCommerce

WordPress plugin that lets customers join a waitlist for out-of-stock WooCommerce products and receive an email when stock returns.

- **Display name:** Oli Back in Stock Notifications for WooCommerce
- **Slug / text domain:** `oli-back-in-stock-notifications`
- **Author:** [Olivier Bigras](https://olivierbigras.com/) (`bigrat95`)
- **License:** GPLv2 or later
- **Requires:** WordPress 6.5+, WooCommerce 9.0+, PHP 7.4+
- **Tested:** WordPress 7.1, WooCommerce 11.2

Install the plugin at the repository root (this folder is the plugin).

## Features

- Notify-me form on out-of-stock simple and variable products (per variation)
- Optional form on backorder
- Guests and logged-in users
- GDPR consent checkbox (optional)
- Double opt-in (optional)
- Honeypot + rate limiting
- Admin subscriptions: filter, search, CSV export, delete, manual notify
- Per-product subscriber count column
- Automatic restock emails (WooCommerce email classes, customizable templates, FR/EN)
- Action Scheduler batching
- One-click unsubscribe with signed tokens
- My Account tab **Stock notifications**
- Signup confirmation email
- Optional admin notification of new signups
- Dashboard stats (signups, notifications sent, conversions via order attribution)
- Shortcode `[oli_bis_notify_form]` and block `oli-bis/notify-form`
- TranslatePress: store language at signup and send email in that locale
- HPOS declared; classic and block product templates

Prefix: `oli_bis_` / `Oli_BIS`. No external HTTP calls, tracking, or upsells.

## Data compatibility

The live PixM store already uses a custom back-in-stock plugin. This 0.1.0 schema is:

`{$wpdb->prefix}oli_bis_subscriptions`

Unique key: `email + product_id + variation_id`.

`Oli_BIS_Migrator` can detect common legacy table names and map rows (email/product/status aliases). When the PixM source arrives, either keep that table if it already matches or run a one-time import into this schema.

## Gaps vs the official WooCommerce Back In Stock Notifications extension

Implemented at parity for the core waitlist flow. Intentionally not included in 0.1.0:

| Official extension | This plugin |
| --- | --- |
| Product Bundles waitlists | Not implemented |
| WooCommerce Subscriptions product types | Supported when Subscriptions is active |
| Create a WP user for every guest signup | Not implemented (official marks this as not recommended) |
| Catalog prompt deep-link that pre-opens the form | Catalog prompt links to the product page |
| Restock notice is reversible *before the first batch* with an admin product-save banner | Queue is cancelled if stock goes out again before/during send; product-save shows a count notice |
| Add notification UI with customer/product autocomplete | Admin add form uses email + product ID |
| 30-day bar chart visualization | Daily table for the same metrics |
| Disable signups per product from Inventory | Same checkbox (`_oli_bis_enabled`) |

## Development

```bash
composer install
composer lint
composer phpcs
composer test
```

GitHub Actions runs PHP syntax checks (7.4 / 8.1 / 8.3), PHPCS WordPress, and PHPUnit.

## Manual test steps

1. Activate on WordPress 7.x with WooCommerce 11.x. Confirm no admin error and HPOS remains compatible.
2. Create a simple product, set it out of stock, view it while catalog hiding of out-of-stock items is off.
3. Sign up as a guest. Confirm the row in WooCommerce → Stock Notifications (status `active`, or `pending` if double opt-in is on).
4. Confirm the signup or verification email (WooCommerce → Settings → Emails).
5. Restock the product. Run Action Scheduler / WP-Cron. Confirm the customer receives the restock email and status becomes `notified`.
6. Click the email product link, place an order, and confirm the dashboard conversion counter increments.
7. Repeat for a variable product, signing up on one out-of-stock variation only.
8. Open My Account → Stock notifications while logged in and unsubscribe.
9. Use the signed unsubscribe link from an email as a guest.
10. Export CSV, delete a row, and send a manual notification from the admin list.
11. Place `[oli_bis_notify_form]` on a page and the block in the editor.
12. Switch the store to French (or TranslatePress `fr`) and confirm the form/email strings are translated.

## Verified locally

On WordPress **7.1.3** + WooCommerce **11.2.1** (PHP 8.3):

- Plugin activates and creates `wp_oli_bis_subscriptions` + `wp_oli_bis_stats_daily`
- Guest signup → restock → Action Scheduler queue → notification email class → status `notified`
- Variation IDs, double-opt-in confirm, signed unsubscribe URL, shortcode, and block registration
