<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}
$_EXTKEY = 'ns_ext_compatibility';


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTask'] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.sendExtensionsReportTask.title',
    'description' => 'LLL:EXT:pb_check_extensions/Resources/Private/Language/locallang.xlf:task.sendReport.description',
    'additionalFields' => 'NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTaskAdditionalFieldProvider'
];


$typoScriptPath = 'Configuration/TypoScript';


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    $typoScriptPath,
    'ns_ext_compatibility'
);
