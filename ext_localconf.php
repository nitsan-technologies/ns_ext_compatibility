<?php

defined('TYPO3') or die;

$_EXTKEY = 'ns_ext_compatibility';

$composerAutoloadFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/Private/Libraries/fpdf/fpdf.php';

require_once($composerAutoloadFile);
