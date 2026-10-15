# Feature — Compatibility report module and scheduler task

*Status: Done (free version). Code-read (2026-10-15); module page seen on v13.4.35, PDF export, modals and the
scheduler task not tested.*

## Module flow (Admin Tools > Extension Report)

1. `listAction()`: TYPO3 version status from `CoreVersionService` (up to date, new / security release,
   outdated, development) and the latest LTS from `get.typo3.org/json`, shown as dismissible notices.
2. Warning when the Extension Manager's TER list is older than 30 days (text differs for Composer mode) and when
   the target version is lower than the installed one.
3. `Partials/Common/Main.html`: header with "Export" (PDF) and the target-version select (12.x / 13.x; default
   from the extension setting `typo3TargetVersion`), then three boxes: system information (site name, TYPO3,
   PHP, pages, root pages, languages), extension overview (installed / not installed / compatible counts) and
   target system requirements (PHP, MySQL, ImageMagick, PHP ini values vs. fixed requirements).
4. `Partials/ExtTable.html`: every local extension except this one, with TER compatibility for 12 / 13, latest
   TER version, and docs links for `ns_*` / `nitsan_*` extensions (`getNewExtensionList()`).
5. Detail and all-versions links load `detailAction` / `viewAllVersionAction` via AJAX into `#nsModel`
   (`Main.js`, see the JavaScript issue in `core.md`).

## Compatibility logic

- Data source: `ExtensionRepository::findByExtensionKeyOrderedByVersion()` (TER list) and
  `ListUtility::getAvailableAndInstalledExtensionsWithAdditionalInformation()`. Extensions not in TER are
  marked `customExt`.
- An extension counts as compatible with N when any TER version has a `typo3` dependency with
  `min <= N <= max`.

## PDF export

`exportXlsAction()` builds the same data and streams `Controller\PDF` (landscape A4) named from
`sheet.filename` + site name; logo and footer from the extension settings.

## Scheduler task

`SendExtensionsReportTask` e-mails installed local extensions that have a TER update (`updateAvailable`),
skipping `excludeExtensionsFromCheck`, rendered with `Templates/Mail/Report.html`. Fields: sender, receivers,
excluded extension keys.
