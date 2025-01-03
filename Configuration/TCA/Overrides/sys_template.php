<?php

defined('TYPO3') || die('Access denied.');

$_EXTKEY = 'ns_ext_compatibility';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript',
    'ns_ext_compatibility'
);
