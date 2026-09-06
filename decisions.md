# Decisions

> Scope: project-local — this file governs only this project root. Cross-project truth lives in the 2nd Brain vault: /Users/vecsatfoxmailcom/Documents/Cowork/Antigravity Cowork/26.06.06 2nd Brain (contract: 00 - System/contracts/project-link-bridge.md).

Use this file for durable choices, supersession, reversals, and rationale.
Keep execution proof in `progress.md` or `vault/sessions/`.

| ID | Date | Decision | Status | Rationale | Evidence | Supersedes |
| --- | --- | --- | --- | --- | --- | --- |
| D-001 | 2026-09-06 | Use V.A.U.L.T. owner-doc structure for project continuity | accepted | Future agents need one owner per truth type | `VAULT.md`, `FILE_MAP_INDEX.md` | - |
| D-002 | 2026-09-06 | Hook `admin_menu` at priority 99 | accepted | Fluent Forms registers `fluent_forms` at default priority 10; priority 99 prevents 403 hook mismatch | Chrome DevTools visual check & `admin_menu` test | - |
| D-003 | 2026-09-06 | Uploads-based disposable domain cache with O(1) flip index | accepted | 8,700+ domains exceed option size limits; local JSON file with array_flip is sub-millisecond | `gm_ff_email_guard_sync_disposable_list()` | - |
| D-004 | 2026-09-06 | Dual transient hook for GitHub Releases auto-updater | accepted | Hooking both read & write transient hooks ensures instantaneous update notifications in wp-admin | Verified across `glintmuse.com` and `xinchaovi.com` | - |
| D-005 | 2026-09-06 | Declarative Uptime Kuma keyword monitoring | accepted | Keyword check on `"enabled":true` catches fatal errors that still return HTTP 200 | Monitor ID 66 in `kuma_apply.py` | - |
| D-006 | 2026-09-06 | Strict Least-Privilege Fine-Grained Token & wp-config.php constant isolation | accepted | Broad CLI tokens create blast radius risks across all user repos on DB breach; Fine-Grained PAT limits scope strictly to this repo with Contents: Read-only, and wp-config.php constant prevents SQL injection leaks | `gm_ff_email_guard_get_github_token()`, `tests/test_email_guard_contract.py` | - |
