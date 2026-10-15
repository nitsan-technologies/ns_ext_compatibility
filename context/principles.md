# ns_ext_compatibility — Operating Principles

*Every change to code, context or docs must serve the goals below.*

---

## Goal 1 — Nothing that works may break

The extension declares TYPO3 v12–v13 (Composer and Classic mode), see `core.md`. TYPO3 14 is not supported yet.

- Keep every declared major working. Follow the existing version switches (`sysDetail.typo3version` checks in
  the templates, `getNumericTypo3Version()` in the controller) instead of inventing new ones.
- Lowest PHP is 8.1 (TYPO3 12): no PHP 8.2+ syntax such as `readonly` classes or DNF types.
- The report relies on the Extension Manager's TER list (`ExtensionRepository`, `ListUtility`) and on
  `get.typo3.org`; keep the report usable when either is outdated or unreachable.
- The free version covers target versions 12.x and 13.x only. Adding a target version means touching the
  controller (`getAllExtensions()`, `detailAction()`, `getSysRequirementForTargetVersion()`), the header
  select, the overview partial, `ext_conf_template.txt` and the PDF class together.
- Prefer the smallest change that fixes the root cause. No drive-by refactors.
- Run `tasks/run-quality.md` and re-test the affected flow before claiming done.

## Goal 2 — Token-cost efficiency (agent context)

- **DRY context:** each fact lives in exactly ONE file. Reference, don't copy.
- **Load on demand:** read `context/features/<area>.md` for the task, not everything.
- **Prune as you go:** keep `context/session-state.md` short.

## Goal 3 — Accuracy and trust

- Answer from context + code, never from invented API, table or field names.
- Mark statements as verified live or code-read; conflicting facts across files = fix the source file.

---

## Guardrails (non-negotiable)

- Installing the premium version through ns_license replaces this extension's folder: never keep user data in
  files below the extension.
- Do not change ns_license from an ns_ext_compatibility task; reuse its module and trial modal.
- `detail` and `viewAllVersion` return HTML fragments for a modal; they must stay without the `Default` layout
  (no strip, no CSS/JS includes).
- All UI text goes into the English `Resources/Private/Language/locallang.xlf`, no hard-coded labels.
  `de.locallang.xlf` is a translation; don't edit it here.

---

## Apply this whenever you

- Edit `context/` or `AGENTS.md`
- Land a feature or bugfix (update the matching `context/features/*.md` + `session-state.md`)
