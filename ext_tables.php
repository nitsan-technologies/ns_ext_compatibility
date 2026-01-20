<?php

use NITSAN\NsExtCompatibility\Task\SendExtensionsReportTask;
use NITSAN\NsExtCompatibility\Task\SendExtensionsReportTaskAdditionalFieldProvider;

defined('TYPO3') || die('Access denied.');

$_EXTKEY = 'ns_ext_compatibility';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SendExtensionsReportTask::class] = [
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.sendExtensionsReportTask.title',
    'description' => 'LLL:EXT:pb_check_extensions/Resources/Private/Language/locallang.xlf:task.sendReport.description',
    'additionalFields' => SendExtensionsReportTaskAdditionalFieldProvider::class
];
