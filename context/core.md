# ns_ext_compatibility — Core facts

## Identity

| Item | Value |
|---|---|
| Extension key | `ns_ext_compatibility` |
| Composer name | `nitsan/ns_ext_compatibility` |
| Title (ext_emconf) / product name | Upgrade Extensions Compatibility / Compatibility Report |
| Namespace | `NITSAN\NsExtCompatibility\` → `Classes/` |
| Version (ext_emconf) | 13.1.0 (latest tag 13.0.1) |
| Repository | https://github.com/nitsan-technologies/ns_ext_compatibility (default branch `master`) |
| Licensing status | **Freemium** — this repo is the free version; a paid premium version exists in the T3Planet catalog (the NITSAN Free/Freemium sheet needs a Google login) |
| Premium version (T3Planet catalog) | "Compatibility Report", €149, https://t3planet.de/en/typo3-upgrade-extension-compatibility |

## Compatibility

| Source | TYPO3 | PHP |
|---|---|---|
| `ext_emconf.php` | `12.0.0-13.9.99` | not declared |
| `composer.json` | `^12.0 \|\| ^13` | not declared |
| ns_license 14.5.4 (dependency) | `12.4.18-14.9.99` | not declared (TYPO3 12.4 needs 8.1+) |

- Effective minimum is **TYPO3 12.4.18** (from ns_license) and **PHP 8.1**; maximum stays 13.

## Dependencies

| Package | Constraint | Why |
|---|---|---|
| `typo3/cms-core` | see above | core |
| `nitsan/ns-license` / `ns_license` | `^14.5.4` / `14.5.4-14.9.99` | TYPO3 Shop module + free-trial deep link (see `features/premium-cta.md`) |

Used but not declared: `typo3/cms-extensionmanager` (TER list), `typo3/cms-install` (`CoreVersionService`),
`typo3/cms-scheduler` (report task). Bundled libraries: FPDF (`Resources/Private/Libraries/fpdf`, loaded with
`require_once` in `ext_localconf.php`), PHPExcel 1.8 (`Classes/PHPExcel-1.8`, unused), jQuery from the TYPO3
backend (`import $ from "jquery"`).

## Tooling

- No PHPUnit, PHPStan, CS fixer or package.json in the repo. `composer.json` has a `.Build/` vendor/web-dir
  config for standalone installs.
- CI: `.github/workflows/ter-release.yml` only (publishes tags to TER with tailor; PHP 7.4, deprecated
  `set-output`).
- `.gitattributes` export-ignores `.github/`, `.gitignore`, `AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/` and
  itself, so they are not in the GitHub/Composer archive or the TER package (the TER release uploads the GitHub
  tag archive).
- Translations: `locallang.xlf` (English source) and `de.locallang.xlf` in the repo; no Crowdin config.
- Extension settings (`ext_conf_template.txt`): `typo3TargetVersion` (12.x / 13.x, default 13.x), `pdflogo`
  (JPG/PNG path), `pdfcopyright`.

## Conventions

- PHP: 4 spaces (`getSysRequirementForTargetVersion()` is not indented), no `declare(strict_types=1)`.
- Fluid and XLF: tabs and spaces mixed.
- Public assets in `Resources/Public/{Css,JavaScript,Icons,Backend/Icons,fonts}/`; backend strip CSS
  `Resources/Public/Css/backend-premium-strip.css`.

## Known issues (existing, not fixed)

All code-read unless marked.

- **Module JavaScript probably never loads:** `Layouts/Default.html` passes the RequireJS-style name
  `TYPO3/CMS/NsExtCompatibility/Main` to `f:be.pageRenderer includeJsFiles`, but `Main.js` is an ES module
  (`import $ from "jquery"`) mapped as `@nitsan/ns-ext-compatibility/` in `JavaScriptModules.php` and never
  loaded through `includeJavaScriptModules`. Without it, changing the target version does not submit the form
  and the detail / all-versions links do not open their modal.
- `listAction()` and `exportXlsAction()` fetch `https://get.typo3.org/json` on every call without error handling.
- Target versions stop at 13.x: no 14.x option, `getAllExtensions()` counts only `compatible12/13`,
  `detailAction()` loops 6–13.
- `exportXlsAction()` exports a PDF (FPDF), not Excel; it ends with `die()`. PHPExcel 1.8 is bundled but unused.
- `Classes/Controller/pdf.php` declares class `PDF` (file name in lower case), which PSR-4 cannot find on
  case-sensitive file systems in Composer mode.
- `SendExtensionsReportTask` imports the missing class `NITSAN\NsExtCompatibility\Utility\Extension`
  (unused); the task description label points to `EXT:pb_check_extensions`.
- `Configuration/TypoScript/setup.typoscript` uses `module.tx_nsextcompatibility.view.*` constants that are not
  defined anywhere; the module works with the default paths.
- `Resources/Private/Language/locallang.xml` and `locallang_nsextcompatibility.xml` are unused.
- `.gitignore` only contains `.git`; README has no badges; `composer.json` uses the deprecated SPDX id
  `GPL-2.0+`; `ext_emconf` description links the old `demo.t3planet.com` / `t3planet.com` hosts.
