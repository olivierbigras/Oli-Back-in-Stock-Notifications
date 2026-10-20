# Changelog

All notable changes to this project are documented in this file.

## 0.1.1 - 2026-10-20

### Added

- Idempotent PixM importer for `{$prefix}wc_back_in_stock_notifications` (dry-run and import; source rows and options are never deleted)
- Dashboard import UI and `legacy_source` / `legacy_id` columns for replay-safe imports
- PixM shortcode alias `[wc_back_in_stock_form]` with `show_only_out_of_stock`
- Optional signup and restock email bodies with `{product_name}`, `{product_url}`, `{site_name}`
- Auto-cleanup of sent notifications after N days, plus a manual cleanup button
- Admin product dropdown filter from existing waitlist products

### Changed

- Signup AJAX accepts supported products that are enabled, matching PixM (shortcode can show the form even when in stock)

## 0.1.0 - 2026-10-20

### Added

- Notify-me form on out-of-stock simple products and variations
- Optional backorder signups, GDPR checkbox, and double opt-in
- Honeypot and IP rate limiting
- WooCommerce email classes for signup, verification, restock, and optional admin alerts
- Action Scheduler batching when stock returns
- Signed confirm and unsubscribe links
- My Account "Stock notifications" endpoint
- Admin list with filter, search, CSV export, delete, and manual notify
- Product waitlist column and inventory enable/disable checkbox
- Dashboard stats for signups, notifications, and order-attributed conversions
- Shortcode and `oli-bis/notify-form` block
- TranslatePress-aware subscriber language on emails
- HPOS compatibility declaration
- French translation and `.pot` file
- Unit tests and GitHub Actions (PHP lint, PHPCS WordPress, PHPUnit)
