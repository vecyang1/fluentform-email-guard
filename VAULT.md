# PROJECT VAULT - 26.09.06-fluentform-email-guard

> Scope: project-local — this file governs only this project root. Cross-project truth lives in the 2nd Brain vault: /Users/vecsatfoxmailcom/Documents/Cowork/Antigravity Cowork/26.06.06 2nd Brain (contract: 00 - System/contracts/project-link-bridge.md).

> Current-state router. Read `AGENTS.md` first for operating rules, then use
> this file to find the active owner docs.

## Snapshot

- Project: 26.09.06-fluentform-email-guard
- Summary: High-performance 6-layer pre-submission email defense, anti-bounce, and typo suggestion engine for Fluent Forms in WordPress with dual-channel distribution (GitHub Releases & official WordPress.org Plugin Directory).
- Current phase: Submitted / In Review Queue (`email-guard-for-fluent-forms`)
- Last updated: 2026-09-07 12:45 by Gemini (Antigravity IDE) — wp-plugin-development, wheel-check
- Health: GREEN
- Existing docs found before init: 0

## Current Goal

- North star: Protect WordPress forms globally from fake/disposable emails, typos, and delivery bounces via a zero-friction, native Fluent Forms integration published on the WordPress.org Directory.
- Near-term outcome: Pass WordPress.org review queue (~300 waiting, 5-8 business days), configure SVN deployment credentials, and trigger automated CI/CD release to official plugin directory.
- Constraints: WordPress.org Guidelines (#17 no leading trademark, #7 no third-party updater in directory builds, GPL-2.0+ license, deterministic header parity between PHP and `readme.txt`).

## Source Pointers

| Truth Type | Owner |
| --- | --- |
| Project rules | `AGENTS.md` |
| Public/community start page | `README.md` only when created by `--public-readme` or human handoff need |
| Design system and brand/UI source of truth | `DESIGN.md` when user-facing, brand, website, app, or visual asset work exists |
| System architecture and module/data/integration map | `docs/architecture.md` |
| Funnel strategy, page/URL map, page inventory, conversion paths, measurement, journey verification, and lead-product inventory | `docs/funnel.md` and `docs/funnel-lead-products.md` when website, ecommerce, lead-gen, launch, creator funnel, course/product funnel, or conversion work exists |
| Current state, source pointers, and risks | `VAULT.md` |
| Active/backlog tasks with Created/Updated dates | `task_plan.md` |
| Dated execution evidence | `progress.md` |
| Latest resume card | `handoff.md` |
| Durable decisions | `decisions.md` |
| Folder and document boundaries | `FILE_MAP_INDEX.md` |
| Durable project docs | `docs/` |
| Runbooks and health checks | `operations/` |
| Live connections and automation cadence | `operations/README.md`, `operations/links.md`, `operations/cadence.md` |
| Assets, imports, exports, source material | `resources/` |
| Skill routes and skills used | `AGENTS.md` for skill roots; `progress.md` for the canonical skills-called log |
| Local project evidence | `vault/` |
| Stable cross-project memory | 2nd Brain `05 - Memory Center` only when reusable outside this project |
| Cross-project router and reciprocal backlink | 2nd Brain project index at `/Users/vecsatfoxmailcom/Documents/Cowork/Antigravity Cowork/26.06.06 2nd Brain/00 - System/registries/project-index.md`; its row should point back to this project root and owner docs |

## Current Risks

- Review Queue Latency: Initial submission entered queue (~300 plugins waiting, approx 5-8 business days). Automated queue watchdog (`wporg_queue_watchdog.py`) monitors status.
- SVN Credentials Provisioning: Pending approval email from `plugins@wordpress.org` to configure GitHub repository secrets `SVN_USERNAME` and `SVN_PASSWORD`.

## Next Actions

1. Maintain automated queue watchdog (`wporg_queue_watchdog.py`) or Uptime Kuma monitoring for WordPress.org directory approval.
2. Upon approval email, add `SVN_USERNAME` and `SVN_PASSWORD` to GitHub Secrets (`vecyang1/fluentform-email-guard`).
3. Tag and push release tag `v1.1.4` to trigger automated 10up GitHub Action SVN deployment.

## Do Not

- Do not store raw evidence in polished docs; use `vault/`.
- Do not move local project evidence into the global 2nd Brain Memory Center.
- Do not duplicate decisions across `VAULT.md` and `decisions.md`.
- Do not claim completion without fresh verification recorded in `progress.md`.
