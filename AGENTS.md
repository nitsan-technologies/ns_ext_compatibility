# AGENTS.md — ns_ext_compatibility (Upgrade Extensions Compatibility)

Agent router for the Extensions Compatibility Report free extension. Load only what your task needs.

> **Principles:** `context/principles.md`
> **Session log:** `context/session-state.md`
> **Doc index:** `context/docs-map.md`

---

## Always load

| File | Why |
|---|---|
| `context/principles.md` | Guardrails, "nothing that works may break" rule |
| `context/core.md` | Identity, TYPO3/PHP/ns_license constraints, tooling, known issues |

---

## Task router

| Task | Load |
|---|---|
| "Extension Report" backend module: system info, statistics, target-version requirements, extension list, detail modals, PDF export | `context/features/compatibility-report.md` |
| Scheduler task "send extensions report" (e-mail) | `context/features/compatibility-report.md` |
| Premium strip / free trial / ns_license dependency | `context/features/premium-cta.md` |
| Files, classes, configuration, assets | `context/architecture.md` |
| Lint / validate / manual test | `tasks/run-quality.md` |
| Update agent context after a change | `tasks/context-update.md` |

---

## Feature status

| Feature | Status | Agent entry |
|---|---|---|
| Compatibility report module (Admin Tools > Extension Report) with PDF export | Done (seen on v13.4.35) | `context/features/compatibility-report.md` |
| Scheduler task e-mailing available extension updates | Done (code-read) | `context/features/compatibility-report.md` |
| Premium strip at the top of the module + ns_license free-trial deep link | Done (needs ns_license 14.5.4 release) | `context/features/premium-cta.md` |

---

## Quick commands

```bash
cd packages/ns_ext_compatibility   # or typo3conf/ext/ns_ext_compatibility in Classic mode
for f in $(find . -name "*.php" -not -path "./.git/*" -not -path "./Classes/PHPExcel-1.8/*"); do php -l "$f"; done
composer validate --no-check-publish
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```
