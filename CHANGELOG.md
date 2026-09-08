# Changelog

All notable changes to the "Email Guard for Fluent Forms" plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.4] - 2026-09-09

### Added
- Bundled canonical dataset of 8,742 disposable email domains offline in `data/disposable_domains.json` for zero-latency, zero-external-network detection.
- Authenticated diagnostics endpoint `/wp-json/fluentform-email-guard/v1/admin/status` requiring `manage_options` capability.

### Changed
- **REST Security**: Guarded `/wp-json/fluentform-email-guard/v1/test` with `current_user_can('manage_options')` and REST API nonce verification.
- **REST Status Sanitization**: Public `/wp-json/fluentform-email-guard/v1/status` now returns minimal health payload (`status`, `enabled`, `version`) to prevent unauthenticated telemetry or IP leakage.
- **Offline Compliance**: Removed remote downloading of disposable lists and disabled WP-Cron scheduled event `gm_ff_email_guard_weekly_sync`.
- **Directory Contributors**: Added submitting contributor `hxsmyxh` alongside `vecyang1`.
- **Repository Visibility**: Made GitHub repository `vecyang1/fluentform-email-guard` public.

### Fixed
- **Plugin Check Compliance**: Added `wp_unslash()`, `absint()`, and array key validation to `$_SERVER['REMOTE_ADDR']`, `$_SERVER['REQUEST_METHOD']`, and `$_POST` inputs.
- **I18n & Escaping**: Replaced unescaped `wp_die(__('...'))` with `wp_die(esc_html__('...', 'email-guard-for-fluent-forms'))`, escaped admin menu labels, settings links, badge counts, and JavaScript REST nonces with `esc_js()`.
- **Timezone Safety**: Replaced PHP `date()` with `gmdate()`.

## [1.1.3] - 2026-09-07

### Added
- Official WordPress.org Plugin Directory preparation and compliance artifacts:
  - Standard `readme.txt` with verified metadata parity (Requires at least: 6.0, Tested up to: 6.7, Requires PHP: 8.0).
  - `.distignore` to prevent development, test, and private files from polluting the SVN release.
  - Discovery assets in `assets/`: `banner-772x250.png`, `banner-1544x500.png`, `icon-128x128.png`, `icon-256x256.png`, `icon.svg`, and `screenshot-1.png`.
- Automated CI/CD workflow `.github/workflows/wporg-deploy.yml` with `WordPress/plugin-check-action` and `10up/action-wordpress-plugin-deploy`.
- Automated status watchdog script `wporg_queue_watchdog.py` in `skills/wp-plugin-development` for review queue progression tracking.

### Changed
- **Trademark Compliance**: Renamed plugin to `Email Guard for Fluent Forms` to strictly conform with WordPress.org Guideline #17 (avoiding leading third-party trademarks).
- **Dual-Channel Distribution**: Abstracted GitHub update hooks with `WPORG_RELEASE` and `FLUENTFORM_EMAIL_GUARD_DISABLE_GH_UPDATER` to prevent conflicts with WordPress core updates (Guideline #7, Decision D-007).
- **Author Alignment**: Updated Author to `World Inspire LLC, Vec` with Author URI `https://worldinspirelab.com/`.

### Security
- Fine-Grained Personal Access Token (FG-PAT) architecture with `Contents: Read-only` isolation.
- File-level token override via `wp-config.php` constant `FLUENTFORM_EMAIL_GUARD_GH_TOKEN`.

## [1.1.2] - 2026-09-06

### Added
- Live diagnostic sandbox in WordPress Admin for instant pre-submission email verification.
- Recent blocked telemetry log table showing timestamps, masked emails, and block reasons.

### Fixed
- Fixed `admin_menu` permission collision by adjusting hook priority to 99 under Fluent Forms.

## [1.1.1] - 2026-09-06

### Added
- Dynamic disposable email domains sync from upstream blocklist (8,700+ domains) with local JSON caching in `uploads/fluentform-email-guard/`.
- In-memory `array_flip` O(1) hash map lookup for zero-latency form submissions.
- Live DNS MX verification via `checkdnsrr` with 24-hour WordPress Transients caching.

## [1.1.0] - 2026-09-06

### Added
- Initial production release with 6-layer defense engine:
  1. RFC 5322 Syntax validation
  2. Custom domain blacklist
  3. Typo auto-suggestion
  4. Disposable/temporary domain filter
  5. Live DNS MX verification
  6. Role-based account filter
- REST API health status endpoint `/wp-json/fluentform-email-guard/v1/status`.
- GitHub Releases native auto-updater integration.
