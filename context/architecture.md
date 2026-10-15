# ns_ext_compatibility — Architecture

One Extbase backend module ("Extension Report") and one scheduler task. No frontend plugin, no content element,
no own tables.

## Configuration

| File | What |
|---|---|
| `ext_localconf.php` | `require_once` FPDF |
| `ext_tables.php` | Scheduler task `SendExtensionsReportTask` with `SendExtensionsReportTaskAdditionalFieldProvider` |
| `ext_conf_template.txt` | `typo3TargetVersion`, `pdflogo`, `pdfcopyright` (see `core.md`) |
| `Configuration/Backend/Modules.php` | `ns_ext_compatibility` below `tools` (Admin Tools, before Extensions), path `/module/tools/nsextcompatibility`, access `user`; actions `list`, `detail`, `viewAllVersion`, `exportXls` |
| `Configuration/Services.yaml` | Autowires `Classes/*` (excluding `Domain/Model`, which does not exist) |
| `Configuration/JavaScriptModules.php` | `@nitsan/ns-ext-compatibility/` → `Resources/Public/JavaScript/` (see `core.md`) |
| `Configuration/ContentSecurityPolicies.php` | Backend CSP: `fonts.googleapis.com` styles, `fonts.gstatic.com` fonts |
| `Configuration/TCA/Overrides/sys_templates.php`, `Configuration/TypoScript/setup.typoscript` | Static template with module view paths (constants missing, see `core.md`) |

## Classes

| Class | Role |
|---|---|
| `Controller\NsExtCompatibilityController` | `listAction` (report page), `detailAction` / `viewAllVersionAction` (modal fragments), `exportXlsAction` (PDF download), `getTrialUrl()` (premium strip) |
| `Controller\PDF` (`pdf.php`) | FPDF subclass: header logo, system / extension / server tables, footer copyright |
| `Domain\Repository\NsExtCompatibilityRepository` | Counts pages, root pages ("domains") and site languages |
| `Task\SendExtensionsReportTask`, `Task\SendExtensionsReportTaskAdditionalFieldProvider` | E-mail of installed local extensions with a TER update (`mailTo`, `mailSender`, `excludeExtensionsFromCheck`) |

## Views

- Module page: `Templates/NsExtCompatibility/List.html` → `Layouts/Default.html` (premium strip, CSS/JS) →
  `Partials/Common/{Main,Header,SysInformation,ExtOverviewReport,TargetSystemRequirement,NsModel}.html`,
  `Partials/ExtTable.html`.
- Modal fragments (no layout): `Templates/NsExtCompatibility/{Detail,ViewAllVersion}.html`.
- Mail: `Templates/Mail/Report.html` (`StandaloneView`).

## Assets

- `Css/main.css`, `Css/backend-premium-strip.css`, `JavaScript/{Main,Datatables}.js` (Datatables import
  commented out), `fonts/`, `Icons/`, `Backend/Icons/`.
