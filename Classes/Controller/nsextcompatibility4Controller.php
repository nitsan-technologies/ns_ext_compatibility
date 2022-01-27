<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Sanjay Chauhan <sanjay@nitsan.in>, NITSAN Technologies
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class TxNsextcompatibilityControllerNsextcompatibility4controller extends Tx_Extbase_MVC_Controller_ActionController
{

    /**
     * Constructor
     * @return void
     */
    public function __construct()
    {
        $this->nsExtRepo = t3lib_div::makeInstance('Tx_NsExtCompatibility_Domain_Repository_NsExtCompatibility4Repository');
        $this->extRepo = t3lib_div::makeInstance('tx_em_Connection_ExtDirectServer');
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $this->downloadBaseUri = 'https://get.typo3.org/';
        $url = $this->downloadBaseUri . 'json';
        $versionJson = GeneralUtility::getUrl($url);
        $ltsVersion = json_decode($versionJson, true);
        $this->view->assign('ltsVersion', $ltsVersion['latest_lts']);
        $this->view->assign('installedVersion', TYPO3_version);

        $sysDetail = $this->getSysDetail();
        //Get typo3 target version from argument and set new target version start
        $arguments = $this->request->getArguments();
        $targetVersion = $arguments['targetVersion'];
        if (isset($targetVersion)) {
            $sysDetail['targetVersion'] = $targetVersion;
        }
        //Get typo3 target version from argument and set new target version end
        $terRepo = $this->extRepo->getRepositories();

        if ($terRepo != null) {
            $date = str_replace('/', '-', $terRepo['data']['0']['updated']);
            $date1 = date('Y-m-d', strtotime($date));
            $date1 = date_create($date1);
            $currentDate = date_create(date('Y-m-d', strtotime('-30 days')));
            $diff = date_diff($date1, $currentDate);
            if ($diff->format('%R%a') > '+1') {
                $this->flashMessageContainer->add($this->translate('warning.TERUpdateText', ['date' => $date]), $this->translate('warning.TERUpdateHeadline'), t3lib_FlashMessage::WARNING);
            }
        }

        //Check last updated Date and give  show warning end

        //Check typo3 target version from extension settings start

        if ($sysDetail['targetVersion'] < $sysDetail['typo3version']) {
            $this->flashMessageContainer->add($this->translate('warning.selectProperTargetVersionText'), $this->translate('warning.selectProperTargetVersionHeadline'), t3lib_FlashMessage::WARNING);
        }

        //Check typo3 target version from extension settings end

        $targetSystemRequirement = $this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);

        //call getAllExtensions() method for fetch extension list
        $assignArray = $this->getAllExtensions($sysDetail['targetVersion']);
        $assignArray['sysDetail'] = $sysDetail;
        $assignArray['targetSystemRequirement'] = $targetSystemRequirement;

        $this->view->assignMultiple($assignArray);
    }

    /*
     * This method is used for fetch all version of passed extension
     */
    public function viewAllVersionAction()
    {
        $arguments = $this->request->getArguments();
        $extension = $arguments['extension'];
        $nsTargetVersion = $arguments['targetVersion'];
        $allVersions = $this->nsExtRepo->getLatestVersionsofExtension($extension);
        $newNsVersion = 0;
        foreach ($allVersions as $key => $updExt) {
            $dependencies = unserialize($updExt['dependencies']);
            if (!empty($dependencies)) {
                foreach ($dependencies['depends'] as $depends => $value) {
                    if ($depends == 'typo3') {
                        $version = explode('-', $value);
                        if ((($version[1] > (int) $nsTargetVersion && $version[1] <= (int) $nsTargetVersion + 1) || $version[0] > (int) $nsTargetVersion && $version[0] <= (int) $nsTargetVersion + 1) && ($newNsVersion < $updExt['version'])) {
                            $newNsVersion = $updExt['version'];
                            $compatVersion = $updExt;
                        }
                    }
                }
            }
        }
        if (empty($compatVersion)) {
            $compatVersion = $allVersions[0];
        }
        $this->view->assign('compatVersion', $compatVersion);
        $this->view->assign('allVersions', array_values($allVersions));
    }

    /**
     * Shows all versions of a specific extension
     *
     * @param string $extensionKey
     * @return void
     */
    public function detailAction()
    {
        $arguments = $this->request->getArguments();
        $extKey = $arguments['extKey'];
        $targetversion = $arguments['targetVersion'];

        $extensionlists = $this->extRepo->getExtensionDetails();
        foreach ($extensionlists['data'] as $key => $extension) {
            if ($extension['doubleInstall'] == 'Local' && $extension['extkey'] == $extKey) {
                $updateToVersion = $this->nsExtRepo->getLatestVersionsofExtension($extension['extkey']);
                if (!empty($updateToVersion)) {
                    foreach ($updateToVersion as $key => $updExt) {
                        $dependencies = unserialize($updExt['dependencies']);
                        if (!empty($dependencies)) {
                            foreach ($dependencies['depends'] as $depends => $value) {
                                if ($depends == 'typo3') {
                                    $version = explode('-', $value);
                                    if (($version[0] < 6 && $version[1] >= 4) || ($version[0] < 6 && $version[1] == 0.0)) {
                                        $extension['compatible4'] = 1;
                                    }
                                    if ($version[0] <= 7 && $version[1] >= 6) {
                                        $extension['compatible6'] = 1;
                                    }
                                    if ($version[0] <= 8 && $version[1] >= 7) {
                                        $extension['compatible7'] = 1;
                                    }
                                    if ($version[0] <= 9 && $version[1] >= 8) {
                                        $extension['compatible8'] = 1;
                                    }
                                    if ($version[0] <= 10 && $version[1] >= 9) {
                                        $extension['compatible9'] = 1;
                                    }
                                    if ($version[0] <= 11 && $version[1] >= 10) {
                                        $extension['compatible10'] = 1;
                                    }
                                    if ($version[0] <= 12 && $version[1] >= 11) {
                                        $extension['compatible11'] = 1;
                                    }
                                    if ($minVersion > $version[0]) {
                                        $minVersion = $version[0];
                                    }
                                    if ((($version[1] > (int) $targetversion && $version[1] <= (int) $targetversion + 1) || $version[0] > (int) $targetversion && $version[0] <= (int) $targetversion + 1) && ($newNsVersion < $extension['version'])) {
                                        $newNsVersion = $updExt['version'];
                                        $extension['newVersion'] = $newNsVersion;
                                    }
                                } else {
                                    if (TYPO3_branch < 6) {
                                        $extension['compatible4'] = 1;
                                    }
                                }
                            }
                        } else {
                            if (TYPO3_branch < 6) {
                                $extension['compatible4'] = 1;
                            }
                        }
                        $extension['updateToVersion'] = $updateToVersion[0];
                    }
                    $extension['type'] = 'TER';
                } else {
                    if (TYPO3_branch < 6) {
                        $extension['compatible4'] = 1;
                    }
                    $extension['type'] = 'Custom';
                }
                $extDetail = $extension;
            }
        }

        $sysDetail = $this->getSysDetail();
        $sysDetail = $this->getSysDetail();
        $sysDetail['targetVersion'] = $targetversion;
        $this->view->assign('sysDetail', $sysDetail);
        $this->view->assign('extension', $extDetail);
    }

    /**
     * This method is used for  get detail list of local extension
     */
    public function getAllExtensions($myTargetVersion)
    {
        $i = 1;
        $totalCompatible4 = $totalCompatible6 = $totalCompatible7 = $totalCompatible8 = $totalCompatible9 = $totalCompatible10 = $totalInstalled = $totalNonInstalled = 0;
        $assignArray = $extensionlist = $overviewReport = [];

        $extensionlists = $this->extRepo->getExtensionDetails();
        $localExtList = [];
        foreach ($extensionlists['data'] as $key => $extension) {
            if ($extension['doubleInstall'] == 'Local' && $extension['extkey'] != 'ns_ext_compatibility') {
                $updateToVersion = $this->nsExtRepo->getLatestVersionsofExtension($extension['extkey']);

                $newNsVersion = 0;
                if (!empty($updateToVersion)) {
                    foreach ($updateToVersion as $key => $updExt) {
                        $dependencies = unserialize($updExt['dependencies']);
                        if (!empty($dependencies)) {
                            foreach ($dependencies['depends'] as $depends => $value) {
                                if ($depends == 'typo3') {
                                    $version = explode('-', $value);
                                    if (($version[0] < 6 && $version[1] >= 4) || ($version[0] < 6 && $version[1] == 0.0)) {
                                        $extension['compatible4'] = 1;
                                    }
                                    if ($version[0] <= 7 && $version[1] >= 6) {
                                        $extension['compatible6'] = 1;
                                    }
                                    if ($version[0] <= 8 && $version[1] >= 7) {
                                        $extension['compatible7'] = 1;
                                    }
                                    if ($version[0] <= 9 && $version[1] >= 8) {
                                        $extension['compatible8'] = 1;
                                    }
                                    if ($version[0] <= 10 && $version[1] >= 9) {
                                        $extension['compatible9'] = 1;
                                    }
                                    if ($version[0] <= 11 && $version[1] >= 10) {
                                        $extension['compatible10'] = 1;
                                    }
                                    if ($version[0] <= 12 && $version[1] >= 11) {
                                        $extension['compatible11'] = 1;
                                    }

                                    if ((($version[1] > (int) $myTargetVersion && $version[1] <= (int) $myTargetVersion + 1) || $version[0] > (int) $myTargetVersion && $version[0] <= (int) $myTargetVersion + 1) && ($newNsVersion < $extension['version'])) {
                                        $newNsVersion = $updExt['version'];
                                        $extension['newVersion'] = $newNsVersion;
                                    }
                                } else {
                                    if (TYPO3_branch < 6) {
                                        $extension['compatible4'] = 1;
                                    }
                                }
                            }
                        } else {
                            if (TYPO3_branch < 6) {
                                $extension['compatible4'] = 1;
                            }
                        }
                        $extension['updateToVersion'] = $updateToVersion[0];
                    }
                    $extension['type'] = 'TER';
                } else {
                    if (TYPO3_branch < 6) {
                        $extension['compatible4'] = 1;
                    }
                    $extension['type'] = 'Custom';
                }
                //Count Total compatibility Start
                if ($extension['compatible4'] == 1) {
                    $totalCompatible4++;
                }
                if ($extension['compatible6'] == 1) {
                    $totalCompatible6++;
                }
                if ($extension['compatible7'] == 1) {
                    $totalCompatible7++;
                }
                if ($extension['compatible8'] == 1) {
                    $totalCompatible8++;
                }
                if ($extension['compatible9'] == 1) {
                    $totalCompatible9++;
                }
                if ($extension['compatible10'] == 1) {
                    $totalCompatible10++;
                }
                if ($extension['compatible11'] == 1) {
                    $totalCompatible11++;
                }
                if ($extension['installed'] == 1) {
                    $totalInstalled++;
                } else {
                    $totalNonInstalled++;
                }
                //Count Total compatibility End

                $localExtList[$key] = $extension;
            }
        }
        //Set overview array start
        $overviewReport['totalInstalled'] = $totalInstalled;
        $overviewReport['totalNonInstalled'] = $totalNonInstalled;
        $overviewReport['totalCompatible4'] = $totalCompatible4;
        $overviewReport['totalCompatible6'] = $totalCompatible6;
        $overviewReport['totalCompatible7'] = $totalCompatible7;
        $overviewReport['totalCompatible8'] = $totalCompatible8;
        $overviewReport['totalCompatible9'] = $totalCompatible9;
        $overviewReport['totalCompatible10'] = $totalCompatible10;
        $overviewReport['totalCompatible11'] = $totalCompatible11;
        //Set overview array end

        $assignArray['overviewReport'] = $overviewReport;
        $assignArray['extensionlist'] = $localExtList;

        return $assignArray;
    }

    /**
     * This method is used of get sysimformation
     */
    public function getSysDetail()
    {
        $sysDetail = [];
        $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_ext_compatibility']);
        $sysDetail['phpversion'] = substr(phpversion(), 0, 6);
        $sysDetail['targetVersion'] = $extConfig['typo3TargetVersion'];
        $sysDetail['sitename'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $sysDetail['typo3version'] = TYPO3_branch;
        $sysDetail['totalPages'] = $this->nsExtRepo->countPages();
        $sysDetail['totalDomain'] = $this->nsExtRepo->countDomain();
        $sysDetail['totalLang'] = $this->nsExtRepo->sysLang();

        return $sysDetail;
    }

    /**
     * This method is used for get System requirement for target typo3 version
     **/
    public function getSysRequirementForTargetVersion($targetVersion)
    {
        exec('convert -version', $imgmagic);
        preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec('mysql -V'), $mysqlVersion);

        $typo3Config = [
            '4.x' => [
                'php' => [
                    'required' => '5.2-5.5',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.5',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '6.x' => [
                'php' => [
                    'required' => '5.3',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.1-5.6',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '7.x' => [
                'php' => [
                    'required' => '5.5',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.5-5.7',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '8.x' => [
                'php' => [
                    'required' => '7',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '9.x' => [
                'php' => [
                    'required' => '7.2',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '10.x' => [
                'php' => [
                    'required' => '7.2',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion[0],
                ],
            ],
            '11.x' => [
                'php' => [
                    'required' => '7.4',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.7',
                    'current' => $mysqlVersion[0],
                ],
            ],
        ];
        return $typo3Config[$targetVersion];
    }

    /**
     * @param string $key
     * @return null|string
     */
    protected function translate($key, $arguments = '')
    {
        if ($arguments != '') {
            return Tx_Extbase_Utility_Localization::translate($key, 'ns_ext_compatibility', $arguments);
        } else {
            return Tx_Extbase_Utility_Localization::translate($key, 'ns_ext_compatibility');
        }
    }
}
