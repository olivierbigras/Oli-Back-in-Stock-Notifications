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
- Shortcode `[oli_bis_notify_form]` (PixM alias `[wc_back_in_stock_form]`) and block `oli-bis/notify-form`
- Safe, idempotent import from the live PixM plugin (dry-run; source data is never deleted)
- Optional custom email bodies and auto-cleanup of sent notifications
- TranslatePress: store language at signup and send email in that locale
- HPOS declared; classic and block product templates

Prefix: `oli_bis_` / `Oli_BIS`. No external HTTP calls, tracking, or upsells.

## Data compatibility (PixM 1.0.0)

Olivier's live plugin **Back in Stock Notifications 1.0.0** (`wc-back-in-stock-notifications`) stores subscribers in:

`{$wpdb->prefix}wc_back_in_stock_notifications`

| PixM column | This plugin |
| --- | --- |
| `id` | `legacy_id` (`legacy_source = pixm`) |
| `product_id` (simple or variation ID) | `product_id` + `variation_id` (parent resolved via `wc_get_product`) |
| `email` | `email` (normalized) |
| `date_added` | `created_at` / `confirmed_at` |
| `is_sent` | `status` = `notified` when set, else `active` |
| `date_sent` | `notified_at` |
| option `wc_bis_email_template` | `notification_body` (imported only if empty) |
| option `wc_bis_confirmation_email_template` | `signup_body` (imported only if empty) |
| option `wc_bis_auto_cleanup` | `auto_cleanup_notified` (30-day cleanup) |

Unique key here: `email + product_id + variation_id`. A second import of the same PixM row is skipped.

### Migration steps

1. Keep `wc-back-in-stock-notifications` installed (or at least leave its table and options in the database). Do not uninstall it if that would drop the table.
2. Activate this plugin. Tables `wp_oli_bis_subscriptions` and `wp_oli_bis_stats_daily` are created; `dbDelta` adds `legacy_source` / `legacy_id` on upgrade from 0.1.0.
3. Open **WooCommerce → Stock dashboard**. The PixM table name and row count are shown when present.
4. Click **Dry-run import**. Confirm the import / skip / invalid counts. Nothing is written.
5. Click **Import subscribers**. Rows are copied; PixM rows and options stay in place.
6. Review WooCommerce → Stock Notifications, then WooCommerce → Settings → Stock Notifications / Emails for imported templates.
7. After a restock email looks correct, deactivate the PixM plugin. Do not delete its table until you are satisfied.

Not imported on purpose: hardcoded `From: info@pixm.com` and gtag tracking.

### Features compared with PixM 1.0.0

| PixM 1.0.0 | This plugin (0.1.1) |
| --- | --- |
| Table `wc_back_in_stock_notifications` | Imported into `oli_bis_subscriptions` |
| Shortcode `[wc_back_in_stock_form]` | Same alias, plus `[oli_bis_notify_form]` and a block |
| Custom email textareas + `{product_name}` / `{product_url}` / `{site_name}` | Same placeholders; WooCommerce email classes wrap them |
| Auto-delete sent rows after 30 days | Setting + daily cron + manual cleanup button |
| Admin product filter dropdown | Dropdown of products that already have subscribers |
| Immediate `wp_mail` on restock | Action Scheduler batches |
| Variations stored as a single `product_id` | Parent + variation columns |
| No GDPR / double opt-in / My Account / CSV / dashboard / HPOS | Included |

## Gaps vs the official WooCommerce Back In Stock Notifications extension

Implemented at parity for the core waitlist flow. Intentionally not included:

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
13. On a site that still has `wp_wc_back_in_stock_notifications`, open WooCommerce → Stock dashboard and confirm the PixM table and row count.
14. Run **Dry-run import**, then **Import subscribers**. Confirm counts, that the PixM table is unchanged, and that a second import reports only skips.
15. Confirm imported `is_sent` rows are `notified` and pending rows are `active`. For a variation ID in PixM, confirm parent + variation columns.
16. Place `[wc_back_in_stock_form]` (and `show_only_out_of_stock="false"`) on a page.

## Verified locally

On WordPress **7.1.3** + WooCommerce **11.2.1** (PHP 8.3):

- Plugin activates and creates `wp_oli_bis_subscriptions` + `wp_oli_bis_stats_daily`
- Guest signup → restock → Action Scheduler queue → notification email class → status `notified`
- Variation IDs, double-opt-in confirm, signed unsubscribe URL, shortcode, and block registration
