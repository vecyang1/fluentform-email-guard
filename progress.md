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
