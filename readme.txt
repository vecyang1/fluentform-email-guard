=== Email Guard for Fluent Forms ===
Contributors: vecyang1
Donate link: https://worldinspirelab.com/
Tags: fluent-forms, email-validation, anti-spam, bounce-prevention, disposable-email
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-grade multi-layer real-time email defense, disposable domain filtration, live DNS MX verification, and typo correction for Fluent Forms.

== Description ==

When running marketing funnels, lead generation campaigns, or automated email onboarding sequences (e.g. FluentCRM, MailPoet, Brevo), invalid email addresses, throwaway mailboxes, and common user typos cause catastrophic bounce rates. Hard bounces permanently damage your domain sender reputation, land marketing broadcasts into spam folders, and trigger transactional rate-limiting from email service providers.

**Email Guard for Fluent Forms** hooks directly into Fluent Forms' core input validation layer (`fluentform/validate_input_item_input_email`) to intercept invalid, disposable, unroutable, and bounce-inducing emails *at form submission time*, before the entry is recorded or forwarded to marketing automations.

### 6-Layer Real-Time Defense Engine

1. **RFC 5322 Syntax Verification**: Native in-memory validation (<0.1ms).
2. **Custom Domain Blacklist**: Intercept abusive domains, competitors, or known bad actors.
3. **Typo Auto-Suggestion**: Detects common domain typos (`gamil.com`, `outlok.com`, `hotmial.com`) and suggests corrections.
4. **Disposable & Temporary Mail Filtration**: Real-time hash lookup across **8,700+ active temporary mail domains** cached in local uploads storage.
5. **Live DNS MX Verification**: Direct DNS query (`checkdnsrr`) to ensure the destination mail server exists, cached via WordPress Transients for 24 hours.
6. **Role-Based Account Filter**: Optional filter for generic mailboxes (`admin@`, `support@`, `billing@`).

### Key Features

* **Real-Time Live Diagnostic Sandbox**: Test any email directly in the admin dashboard before enabling rules globally.
* **Granular Layer Toggles**: Turn MX check, disposable filter, typo hints, or role filter on/off independently.
* **Zero-Latency In-Memory Lookups**: Domain lookups indexed via `array_flip` O(1) hash maps.
* **REST API Health Endpoint**: `/wp-json/fluentform-email-guard/v1/status` for external uptime watchdogs.
* **Audit Telemetry Log**: Review the last 100 blocked submissions with timestamp, masked email, and block reason.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/email-guard-for-fluent-forms`, or install directly through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Fluent Forms > Email Guard** to configure defense rules and test addresses in the live sandbox.

== Frequently Asked Questions ==

= Does this require Fluent Forms Pro? =
No, Email Guard for Fluent Forms works seamlessly with both the Free and Pro editions of Fluent Forms.

= Does it slow down form submissions? =
No. Syntax, blacklist, typo, and disposable checks execute in memory (<0.5ms). DNS MX checks are cached for 24 hours via WordPress Transients so repeat queries have zero latency.

= Where does the disposable domains list come from? =
The plugin bundles an initial snapshot of over 8,700 disposable domains and provides automated local caching in `wp-content/uploads/fluentform-email-guard/disposable_domains.json`.

= Can I whitelist corporate or customer domains? =
Yes. You can add specific domains to the whitelist section to bypass MX and disposable checks.

== External Services ==

This plugin can optionally synchronize an updated list of disposable email domains via WP-Cron:
* Service: Disposable Email Domains Blocklist by disposable-email-domains (https://github.com/disposable-email-domains/disposable-email-domains)
* Purpose: Refreshes the local JSON list of temporary disposable domains to protect against new throwaway inbox providers.
* Privacy: No personal data or form submission content is ever transmitted to GitHub or any third party. The plugin executes a standard HTTP GET request to download the public domain text file.
* Terms / License: https://github.com/disposable-email-domains/disposable-email-domains/blob/master/LICENSE

== Screenshots ==

1. Admin Dashboard with Live Interactive Sandbox and Granular Defense Toggles.
2. Form submission inline rejection notice when a temporary email is submitted.
3. Audit table showing recent blocked attempts with timestamps and reasons.

== Changelog ==

= 1.1.3 =
* Compliance: Renamed to "Email Guard for Fluent Forms" for WordPress.org trademark directory guidelines.
* Architecture: Dual-channel release support with release-guarded updater filters.
* Security: Implemented fine-grained token isolation and file constant support.

= 1.1.1 =
* Optimization: Dynamic disposable domains synchronization and local caching.
* Reliability: Restored priority 99 on admin_menu hook to prevent permission collisions.

= 1.1.0 =
* Initial public release with 6-layer defense engine and live testing sandbox.
