# Task Plan

> Active and near-term work only. Keep this file small; archive old
> completed/dropped rows before it becomes a giant database.
> Every task row includes `Created` (first entered into this ledger) and
> `Updated` (last change to the row, status, or evidence), both as `YYYY-MM-DD`.

## Completed Work

| ID | Status | Created | Updated | Task | Owner | Next Action | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| T-001 | completed | 2026-09-06 | 2026-09-06 | Fix wp-admin 403 menu hook permission bug | Agent | None | Priority 99 registered, verified visually in Chrome |
| T-002 | completed | 2026-09-06 | 2026-09-06 | Build 6-layer email defense engine | Agent | None | `fluentform-email-guard.php` passed syntax & contract tests |
| T-003 | completed | 2026-09-06 | 2026-09-06 | Setup GitHub private repo & CI/CD release workflow | Agent | None | `vecyang1/fluentform-email-guard` releases `v1.1.0` & `v1.1.1` |
| T-004 | completed | 2026-09-06 | 2026-09-06 | WordPress native GitHub auto-updater | Agent | None | Verified real-time detection via `site_transient_update_plugins` |
| T-005 | completed | 2026-09-06 | 2026-09-06 | Deploy fleet across estate sites | Agent | None | `glintmuse.com`, `xinchaovi.com`, `belovedpals.com` running v1.1.1 |
| T-006 | completed | 2026-09-06 | 2026-09-06 | Uptime Kuma declarative monitoring | Agent | None | Monitor ID 66 applied, 0 unmapped monitors in `kuma_apply.py` |
| T-007 | completed | 2026-09-06 | 2026-09-06 | Deploy to remaining estate sites (vectory42/hi.carradiocodes) | Agent | None | v1.1.3 active on hi.carradiocodes.co.uk, 8,725 domains synced, two-sided E2E verified |
| T-008 | completed | 2026-09-06 | 2026-09-06 | Deploy to vectory44.sg-host.com (Yua Dear Studio) | Agent | None | v1.1.3 active, FG-PAT token configured, two-sided E2E verified |
| T-009 | completed | 2026-09-06 | 2026-09-06 | Least-privilege FG-PAT token rotation & zero blast radius verification | Agent | None | Fine-Grained Token active across entire 6-site fleet; broad tokens purged; 0 access to other repos confirmed |
| T-010 | completed | 2026-09-06 | 2026-09-06 | Register product into Product[OS] Notion Database | Agent | None | Page ID 3d3e1b43-2393-8108-84f9-fa0e09befe75 created with status Shipped & 5-star rating |
| T-011 | completed | 2026-09-06 | 2026-09-06 | Store GitHub PAT in 1Password Agent Automation vault | Agent | None | Item ID blxjpjlvp3ng56j7lys3u4dd6i created & verified via canonical save_credential.py |
| T-012 | completed | 2026-09-07 | 2026-09-07 | Standardize WordPress.org Directory Publishing Tooling | Agent | None | `skills/wp-plugin-development` wporg_preflight_check.mjs & 10/10 tests passing |
| T-013 | completed | 2026-09-07 | 2026-09-07 | Upgrade fluentform-email-guard for WordPress.org submission | Agent | None | Name compliant, readme.txt, .distignore, assets/, preflight audit passed 100% |
| T-014 | completed | 2026-09-07 | 2026-09-07 | Submit initial package to WordPress.org plugin directory | Vec | None | `email-guard-for-fluent-forms.zip` submitted at `add/`, entered review queue (~300 waiting) |
| T-015 | completed | 2026-09-07 | 2026-09-07 | Review queue monitoring & automated watchdog alert | Agent | None | `wporg_queue_watchdog.py` built & verified; exit code 2 (pending) and exit code 0 (approved) tested |
| T-016 | waiting | 2026-09-07 | 2026-09-07 | WordPress.org review queue clearance & SVN credentials | Vec + Agent | Await review email from `plugins@wordpress.org` | Automated Scan: PASS. 261 plugins waiting in queue, email confirmation sent to `yanghxmail@gmail.com` |
| T-017 | completed | 2026-09-07 | 2026-09-07 | Resolve WordPress.org automated scanner check failures | Agent | None | Purged updater hooks (Guideline #7), updated Tested up to 7.1, slug-matched textdomain, uploaded zip passed scanner cleanly |

## Backlog

- [ ] **Configure SVN Credentials & Release**: Upon review approval email from `plugins@wordpress.org`, add `SVN_USERNAME` and `SVN_PASSWORD` to GitHub Secrets, then push tag `v1.1.4` to trigger 10up SVN deploy.



