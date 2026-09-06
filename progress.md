# Progress

Use this file for dated execution evidence, verification outputs, blockers, and
meaningful state changes. Do not turn `VAULT.md` into a session diary.

## 2026-09-06 12:41

- Initialized or prepared the V.A.U.L.T. project knowledge structure.
- Evidence paths: `VAULT.md`, `AGENTS.md`, `task_plan.md`, `handoff.md`,
  `FILE_MAP_INDEX.md`, `vault/README.md`.
- Skills used: `init-vault-method` for scaffold creation.

## 2026-09-06 12:55 - Production Release v1.1.1 & Fleet Deployment

- Created private GitHub repository: `vecyang1/fluentform-email-guard`.
- Published GitHub Releases: `v1.1.0` and `v1.1.1` with attached `fluentform-email-guard.zip` distribution package.
- Built GitHub Actions workflow `.github/workflows/ci-release.yml` with 4-version PHP matrix testing + automated release zip packaging (all runs passing green).
- Deployed and verified live across 3 WordPress production sites:
  - `glintmuse.com`: v1.1.1 active, 8,719 disposable domains, wp-admin verified in Chrome, REST API verified.
  - `xinchaovi.com`: v1.1.1 active, 8,727 disposable domains, live rejection test passed (`someone@mailinator.com`), typo correction test passed (`someone@gamil.com` -> `someone@gmail.com`), valid email passed (`contact@xinchaovi.com`).
  - `belovedpals.com`: v1.1.1 active, 8,727 disposable domains, REST API verified.
- Uptime Kuma Declarative Watchdog:
  - Added monitor ID 66 (`GlintMuse FluentForm Email Guard`) into `26.08.16-adnova-cli/scripts/kuma_apply.py`.
  - Added keyword check on `"enabled":true` at interval 300s.
  - Executed `kuma_apply.py` against live SQLite DB on `openclaw-eu`: 0 unmapped monitors, verified clean exit.
- Skills used: `wheel-check`, `chrome-devtools`, `novamira-ops`, `init-vault-method`.

## 2026-09-06 13:45 - Security Hardening & Fine-Grained Token Isolation (v1.1.3)

- Threat Modeling & Blast Radius Resolution:
  - Identified that storing broad CLI OAuth tokens (`gho_...`) creates critical lateral risk across all private GitHub repos upon site/DB compromise.
  - Implemented GitHub Fine-Grained Personal Access Token (FG-PAT) architecture (`Contents: Read-only`, scoped strictly to `vecyang1/fluentform-email-guard`).
  - Added physical file-level isolation via `wp-config.php` constant `FLUENTFORM_EMAIL_GUARD_GH_TOKEN` over database `wp_options`.
  - Upgraded Admin UI to mask stored tokens, support `__CLEAR__` keyword, and show file isolation status.
  - Upgraded `tests/test_email_guard_contract.py` with 8 comprehensive contract tests (passing 100%).
  - Codified standard into `wp-plugin-development` skill (`references/github-auto-updater.md`, Section 4).

## 2026-09-06 14:00 - Deployment to vectory44.sg-host.com (Yua Dear Studio) & Two-Sided E2E Verification

- Deployed `fluentform-email-guard` to `https://vectory44.sg-host.com/`:
  - Active in WordPress (`is_plugin_active: YES`).
  - 8,727 disposable domains synced into `wp-content/uploads/fluentform-email-guard/disposable_domains.json`.
  - REST status endpoint verified: `https://vectory44.sg-host.com/wp-json/fluentform-email-guard/v1/status` (HTTP 200).
- Admin Dashboard verified via Chrome DevTools:
  - Accessible at `/wp-admin/admin.php?page=fluentform-email-guard`.
  - Defense layers: Syntax, DNS MX, Disposable, Typo Correction, Custom Blocked all active.
- Two-Sided Front-End Browser E2E Verification (Form #3 on `https://vectory44.sg-host.com/`):
  - **Negative Test 1 (Disposable Email)**: `tester@mailinator.com` -> Rejected with inline red error: *"Temporary or disposable email addresses are not accepted."* (Saved to telemetry log).
  - **Negative Test 2 (Typo Domain)**: `tester@gamil.com` -> Rejected with inline red suggestion: *"Did you mean gmail.com? Please verify your email."* (Saved to telemetry log).
  - **Negative Test 3 (Non-existent MX)**: `tester@fakeinvaliddomain999xyztest.org` -> Rejected with inline red error: *"The domain of this email cannot receive mail. Please check for typos."* (Saved to telemetry log).
  - **Positive Test (Legitimate Email)**: `client.patron@gmail.com` -> Accepted with HTTP 200, inline success notice rendered (*"Thank you for reaching out! Your inquiry has been received..."*), entry persisted to Fluent Forms DB (`insert_id: 6`, `serial_number: 4`).
  - **Admin Telemetry Audit Log**: Verified 3 newly captured rejected events visible in real-time in the admin audit table with masked emails, domain names, reason badges, and client IP.

## 2026-09-06 14:10 - Fleet-Wide FG-PAT Rotation & Product[OS] Registration (v1.1.3)

- **Zero Blast Radius Token Rotation Across 6 Production Sites**:
  - `github_pat_11A7...` applied to `glintmuse.com`, `xinchaovi.com`, `belovedpals.com`, `worldinspirelab.com`, `vectory44.sg-host.com`, `hi.carradiocodes.co.uk`.
  - Old broad CLI OAuth tokens completely purged from `wp_options`.
  - All 6 sites verified on `v1.1.3` returning `"version":"1.1.3"` and `"enabled":true` via `/wp-json/fluentform-email-guard/v1/status`.
  - Two-sided E2E tests (disposable, typo, legitimate) passing 100% on all 6 sites.
- **Product[OS] Notion Database Ingestion**:
  - Registered product into authoritative database [Product[OS]](https://app.notion.com/p/dvvv/251e1b432393802e9d47f79037c1794d).
  - Page URL: `https://app.notion.com/p/Fluent-Forms-Email-Guard-Anti-Bounce-fluentform-email-guard-3d3e1b432393810884f9fa0e09befe75` (Page ID: `3d3e1b43-2393-8108-84f9-fa0e09befe75`).
  - Pipeline: `Shipped`, Rating: `⭐⭐⭐⭐⭐`, Tag: `Product, Skill`, Product Role: `Standalone`.
  - Authoritative re-read executed to ensure Single Source of Truth (SSOT) & Unidirectional Data Flow consistency.

