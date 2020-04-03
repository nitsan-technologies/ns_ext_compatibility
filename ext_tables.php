<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$_EXTKEY = 'ns_ext_compatibility';
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
			array(
				'nsextcompatibility4' => 'list, viewAllVersion,detail',

			),
			array(
				'access' => 'user,group',
				'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
				'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:module.title',
			)
		);
	}
	t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/nsextcompatibility4', 'ns_ext_compatibility');

	require_once(
	    t3lib_extMgm::extPath($_EXTKEY) . 'Classes/PHPExcel-1.8/PHPExcel.php'
	);


}else{
	if (TYPO3_MODE === 'BE') {
		require_once(
	    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/PHPExcel-1.8/PHPExcel.php'
	);

		/**
		 * Registers a Backend Module
		 */
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
			'NITSAN.' . $_EXTKEY,
			'tools',	 // Make module a submodule of 'tools'
			'nsextcompatibility',	// Submodule key
			'',						// Position
			array(
				'nsextcompatibility' => 'list,viewAllVersion,detail'
			),
			array(
				'access' => 'user,group',
                'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/Extension.svg',
				'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:module.title',
			)
		);

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTask'] =array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.sendExtensionsReportTask.title',
		'description' => 'LLL:EXT:pb_check_extensions/Resources/Private/Language/locallang.xlf:task.sendReport.description',
		'additionalFields' => 'NITSAN\\NsExtCompatibility\\Task\\SendExtensionsReportTaskAdditionalFieldProvider'
		);
	}

	require_once(
	    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/PHPExcel-1.8/PHPExcel.php'
	);

	if (version_compare(TYPO3_branch, '6.2', '<')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/nsextcompatibility6/6.1', 'ns_ext_compatibility');
	}elseif(version_compare(TYPO3_branch, '7.0', '<')){

	    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/nsextcompatibility6/6.2', 'ns_ext_compatibility');
	}else{

		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'ns_ext_compatibility');
	}
}

?>
