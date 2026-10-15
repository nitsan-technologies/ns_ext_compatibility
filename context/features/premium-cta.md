# Feature — Premium strip and ns_license free trial

*Status: Done. Verified live on TYPO3 13.4.35 (2026-10-15): strip at the top of the "Extension Report" module;
the button opens the ns_license module with `trial=ns_ext_compatibility#free-section`. ns_license there is
14.5.1, so the trial modal did not preselect the product; that needs ns_license 14.5.4 (deep-link support),
not yet released. v12 not tested.*

## Licensing status

Freemium. Premium product (T3Planet catalog): "Compatibility Report", €149,
https://t3planet.de/en/typo3-upgrade-extension-compatibility (not linked from the UI; the trial stays inside the
TYPO3 backend). Catalog features: compatibility report for a target TYPO3 version, system information report,
statistics of installed and compatible extensions, server compatibility report, PDF or Excel download with your
own logo.

## Dependency

`composer.json` `"nitsan/ns-license": "^14.5.4"`, `ext_emconf.php` `'ns_license' => '14.5.4-14.9.99'`. Raises
the effective minimum to TYPO3 12.4.18 (see `core.md`).

## Placement

The extension has no content element or FlexForm, so the strip sits only in its backend module:

| Place | Position |
|---|---|
| "Extension Report" module (`ns_ext_compatibility`, Admin Tools) | Top of the module body, above the TYPO3 version notices and the report |

Not in the `detail` / `viewAllVersion` modal fragments (they have no layout).

## How it works

- `NsExtCompatibilityController::getTrialUrl()` builds route `nitsan_nslicensemodule` +
  `trial=ns_ext_compatibility` + `#free-section`; returns `''` (`RouteNotFoundException`) without ns_license.
  `listAction()` assigns it as `trialUrl`.
- `Layouts/Default.html` (used only by `List.html`) renders the strip inside `<f:if condition="{trialUrl}">`:
  `actions-rocket` icon (`core:icon`), headline and description on two lines, `btn btn-primary`
  "Start Free Trial →" (the arrow is `aria-hidden` markup), same frame. It also links
  `Css/backend-premium-strip.css`.

## Strip

- Labels in `locallang.xlf`: `premium.strip.headline` "You're using the free version of NS Extension
  Compatibility", `premium.strip.description` "Unlock premium features and get more out of NS Extension
  Compatibility.", `premium.strip.trial` "Start Free Trial".
- `Resources/Public/Css/backend-premium-strip.css`: `.ns-ext-compatibility-premium-strip` — team-wide design
  (light-blue card `#e7f2fc`, border `#8eb6e8`, 4px `#2b6cb0` left accent, 6px radius, padding 14px 18px,
  `light-dark()` for dark mode, button `#007bff` / hover `#0062cc`).
- Measured on v13.4.35: 850×67px at the top of the module body.
- ns_license's module requires system maintainer access; other users get access denied after clicking.
