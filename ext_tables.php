<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
$_EXTKEY = 'ns_ext_compatibility';
// @extensionScannerIgnoreLine
use NITSAN\NsExtCompatibility\Controller\NsExtCompatibilityController;

if (version_compare(TYPO3_branch, '6.0', '<')) {
    if (TYPO3_MODE === 'BE') {
        /**
         * Registers a Backend Module
         */
        Tx_Extbase_Utility_Extension::registerModule(
            $_EXTKEY,
            'tools',	 // Make module a submodule of 'tools'
            'nsextcompatibility',	// Submodule key
            '',						// Position
            [
                'nsextcompatibility4' => 'list, detail',

            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/ns_ext_compatibility.svg',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:module.title',
            ]
        );
    }
    t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/nsextcompatibility4', 'ns_ext_compatibility');
} else {
    if (TYPO3_MODE === 'BE') {

        if (version_compare(TYPO3_branch, '11.0', '>=')) {
            $moduleClass = NsExtCompatibilityController::class;
        } else {
            $moduleClass = 'NsExtCompatibility';
        }

        /**
         * Registers a Backend Module
         */
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'NITSAN.' . $_EXTKEY,
            'tools',	 // Make module a submodule of 'tools'
            'nsextcompatibility',	// Submodule key
            '',						// Position
            [
                $moduleClass => 'list,detail'
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/ns_ext_compatibility.svg',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:module.title',
            ]
        );

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTask'] = [
            'extension' => $_EXTKEY,
            'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.sendExtensionsReportTask.title',
            'description' => 'LLL:EXT:pb_check_extensions/Resources/Private/Language/locallang.xlf:task.sendReport.description',
            'additionalFields' => 'NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTaskAdditionalFieldProvider'
        ];
    }

    $typoScriptPath = 'Configuration/TypoScript';

    if (version_compare(TYPO3_branch, '6.2', '<')) {
        $typoScriptPath .= '/nsextcompatibility6/6.1';
    } elseif (version_compare(TYPO3_branch, '7.0', '<')) {
        $typoScriptPath .= '/nsextcompatibility6/6.2';
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $_EXTKEY,
        $typoScriptPath,
        'ns_ext_compatibility'
    );

}
