# ns_ext_compatibility — Session state

## 2026-10-15 — ns_license integration + premium strip + agent context

- Branch `feature/ns-license-premium-strip` from `master` (6af3f55).
- Added `nitsan/ns-license ^14.5.4` / `ns_license 14.5.4-14.9.99` (effective minimum now TYPO3 12.4.18).
  Version 13.0.1 → 13.1.0.
- Premium strip with "Start Free Trial" → ns_license module `?trial=ns_ext_compatibility#free-section` at the
  top of the "Extension Report" module, team-wide wording and design (see `features/premium-cta.md`).
- Verified live on TYPO3 13.4.35 classic / PHP 8.4: extension temporarily added to `PackageStates.php`,
  `serverConnectionTime` cookie set before login so the ns_license check could not rename files, temporary
  system-maintainer admin. All removed; `PackageStates.php` and `settings.php` restored byte-identical; no
  other extension's files changed; the extension is **not active** in that install. ns_license there is
  14.5.1, so the trial modal did not open by itself. PHP 8.4 and 8.1 lint clean.
- Created agent context (`AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/`) and `.gitattributes`; no previous
  context existed.
- Open: ns_license 14.5.4 not yet published; v12 not tested; module JavaScript issue (see `core.md`).
