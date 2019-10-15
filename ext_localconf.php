<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$composerAutoloadFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/Private/Libraries/fpdf/fpdf.php';

require_once($composerAutoloadFile);
	
?>