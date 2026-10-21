# Changelog

All notable changes to this project are documented in this file.

## 0.1.4 - 2026-10-21

### Changed

- Distributable ZIP excludes development files via `.distignore` and `.gitattributes` `export-ignore` (tests, CI, Composer, package manifests, and internal Markdown; `readme.txt` is kept)

## 0.1.3 - 2026-10-21

### Fixed

- Legacy shortcode aliases from `oli_bis_legacy_map` are registered on `init`, so a filter in the theme `functions.php` is honored
- A mapped shortcode alias is skipped when another plugin already registered that tag (`shortcode_exists`)

### Added

- `{site_name}` is an alias of `{site_title}` in email templates

## 0.1.2 - 2026-10-21

### Changed

- Removed built-in third-party shortcode aliases and hardcoded waitlist importer keys
- Legacy migration is now generic and empty by default via the `oli_bis_legacy_map` filter (old option keys, metas, and shortcodes toward the current ones; optional table suffixes)
- Existing imported subscriptions keep working; source tables, options, and metas are never deleted
- Documented how to register a previous shortcode tag through the same filter

## 0.1.1 - 2026-10-20

### Added

- Idempotent importer for a previous waitlist table (dry-run and import; source rows and options are never deleted)
- Dashboard import UI and `legacy_source` / `legacy_id` columns for replay-safe imports
- Optional shortcode alias support with `show_only_out_of_stock`
- Optional signup and restock email bodies with `{product_name}`, `{product_url}`, `{site_name}`
- Auto-cleanup of sent notifications after N days, plus a manual cleanup button
- Admin product dropdown filter from existing waitlist products

### Changed

- Signup AJAX accepts supported products that are enabled (shortcode can show the form even when in stock)

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
