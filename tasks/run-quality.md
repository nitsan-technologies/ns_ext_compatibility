# Run quality checks

The repo has no PHPUnit, PHPStan or CS config. Available checks:

```bash
# PHP syntax (skip the bundled PHPExcel); also run once with PHP 8.1, the lowest version TYPO3 12 supports
for f in $(find . -name "*.php" -not -path "./.git/*" -not -path "./.Build/*" -not -path "./Classes/PHPExcel-1.8/*"); do php -l "$f"; done

# composer.json
composer validate --no-check-publish

# XLF well-formedness
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```

## Manual test (per TYPO3 major 12 / 13)

1. Admin Tools > Extension Report: strip at the top, above the version notices; the report renders.
2. "Start Free Trial" (as system maintainer) opens the ns_license module and its trial modal with
   "Compatibility Report (ns_ext_compatibility)" preselected (ns_license 14.5.4+). Cancel; don't start a trial.
3. Deactivate ns_license (test system only): no strip, the module still works.
4. Change the target version, open an extension's detail and all-versions modal, export the PDF.
5. Scheduler: create the report task with sender and receiver, run it, check the mail.
6. Check light and dark backend themes.

On a shared install, activate the extension only temporarily and set the `serverConnectionTime` cookie before
the backend login, otherwise the ns_license check may rename files of unlicensed NITSAN extensions.
