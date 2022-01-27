<?php
namespace NITSAN\NsExtCompatibility\Controller;

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
use NITSAN\NsExtCompatibility\Utility\Extension;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Annotation\Inject as inject;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Backend Controller
 */
class nsextcompatibilityController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     * @inject
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     * @inject
     */
    protected $repositoryRepository;

    /**
     * @var \NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository
     * @inject
     */
    protected $NsExtCompatibilityRepository;

    /**
     * This method is used for fetch list of local extension
     */
    public function listAction()
    {
        $sysDetail = $this->getSysDetail();
        //Get typo3 target version from argument and set new target version start
        $arguments = $this->request->getArguments();
        $targetVersion = $arguments['targetVersion'];
        if (isset($targetVersion)) {
            $sysDetail['targetVersion'] = $targetVersion;
        }
        //Get typo3 target version from argument and set new target version end
        $terRepo = $this->repositoryRepository->findOneTypo3OrgRepository();
        //Check last updated Date and give  show warning start
        if ($terRepo != null) {
            $lastUpdatedTime = $terRepo->getLastUpdate();
            $currentTime = strtotime('-30 days');
            if (version_compare(TYPO3_branch, '6.2', '<')) {
                if (date('Y-m-d', $currentTime) > $lastUpdatedTime->format('Y-m-d')) {
                    $TERUpdateMessage = GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        $this->translate('warning.TERUpdateText', ['date' => $lastUpdatedTime->format('Y-m-d')]),
                        $this->translate('warning.TERUpdateHeadline'), // the header is optional
                        \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                    );

                    \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($TERUpdateMessage);
                }
            } else {
                if (date('Y-m-d', $currentTime) > $lastUpdatedTime->format('Y-m-d')) {
                    $this->addFlashMessage($this->translate('warning.TERUpdateText', ['date' => $lastUpdatedTime->format('Y-m-d')]), $this->translate('warning.TERUpdateHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
                }
            }
        }
        //Check last updated Date and give  show warning end

        //Check typo3 target version from extension settings start
        if (version_compare(TYPO3_branch, '6.2', '<')) {
            if ($sysDetail['targetVersion'] < $sysDetail['typo3version']) {
                $selectProperTargetVersionMessage = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $this->translate('warning.selectProperTargetVersionText'),
                    $this->translate('warning.selectProperTargetVersionHeadline'), // the header is optional
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                );

                \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($selectProperTargetVersionMessage);
            }
        } else {
            if ((int)$sysDetail['targetVersion'] < $sysDetail['typo3version']) {
                $this->addFlashMessage($this->translate('warning.selectProperTargetVersionText'), $this->translate('warning.selectProperTargetVersionHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
            }
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
        $allVersions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extension);
        $newNsVersion = 0;
        foreach ($allVersions as $extension) {
            foreach ($extension->getDependencies() as $dependency) {
                if ($dependency->getIdentifier() === 'typo3') {
                    // Extract min TYPO3 CMS version (lowest)
                    $minVersion = $dependency->getLowestVersion();
                    // Extract max TYPO3 CMS version (higherst)
                    $maxVersion = $dependency->getHighestVersion();
                    if ((($maxVersion > (int) $nsTargetVersion && $maxVersion <= (int) $nsTargetVersion + 1) || $minVersion > (int) $nsTargetVersion && $minVersion <= (int) $nsTargetVersion + 1) && ($newNsVersion < $extension->getVersion())) {
                        $compatVersion = $extension;
                    }
                }
            }
        }
        if (empty($compatVersion)) {
            $compatVersion = $allVersions[0];
        }
        $this->view->assign('compatVersion', $compatVersion);
        $this->view->assign('allVersions', $allVersions);
    }

    /**
     * Shows all versions of a specific extension
     *
     * @param string $extensionKey
     * @return void
     */
    public function detailAction()
    {
        $totalCompatible4 = 0;
        $totalCompatible6 = 0;
        $totalCompatible7 = 0;
        $totalCompatible8 = 0;
        $totalCompatible9 = 0;
        $totalCompatible10 = 0;
        $totalCompatible11 = 0;
        $totalInstalled = 0;
        $totalNonInstalled = 0;
        $arguments = $this->request->getArguments();
        $extKey = $arguments['extKey'];
        $detailTargetVersion = $arguments['targetVersion'];
        //Get extension list
        $myExtList = $this->objectManager->get(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        foreach ($allExtensions as $extensionKey => $nsExt) {
            $newNsVersion = 0;
            //Filter all local extension for whole TER data start
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key'] == $extKey) {
                $extArray = $this->extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);
                //Fetch typo3 depency of extesion  start
                if (count($extArray) != 0) {
                    foreach ($extArray as $extension) {
                        foreach ($extension->getDependencies() as $dependency) {
                            if ($dependency->getIdentifier() === 'typo3') {
                                // Extract min TYPO3 CMS version (lowest)
                                $minVersion = $dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion = $dependency->getHighestVersion();
                                if ($minVersion <= 7 && $maxVersion >= 6) {
                                    $nsExt['compatible6'] = 1;
                                }
                                if ($minVersion <= 8 && $maxVersion >= 7) {
                                    $nsExt['compatible7'] = 1;
                                }
                                if ($minVersion <= 9 && $maxVersion >= 8) {
                                    $nsExt['compatible8'] = 1;
                                }
                                if ($minVersion <= 10 && $maxVersion >= 9) {
                                    $nsExt['compatible9'] = 1;
                                }
                                if ($minVersion <= 11 && $maxVersion >= 10) {
                                    $nsExt['compatible10'] = 1;
                                }
                                if ($minVersion <= 12 && $maxVersion >= 11) {
                                    $nsExt['compatible11'] = 1;
                                }
                                if ((($maxVersion > (int) $detailTargetVersion && $maxVersion <= (int) $detailTargetVersion + 1) || $minVersion > (int) $detailTargetVersion && $minVersion <= (int) $detailTargetVersion + 1) && ($newNsVersion < $extension->getVersion())) {
                                    $newNsVersion = $extension->getVersion();
                                    $nsExt['newVersion'] = $newNsVersion;
                                }
                            }
                        }
                    }
                } else {
                    $nsExt['customExt'] = true;
                }
                //Fetch typo3 depency of extesion  end

                // Set overview Report start
                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }
                if ($extArray[0]) {
                    $nsExt['newUplaodComment'] = $extArray[0]->getUpdateComment();
                    $nsExt['newLastDate'] = $extArray[0]->getLastUpdated();
                    $nsExt['newAlldownloadcounter'] = $extArray[0]->getAlldownloadcounter();
                }

                //Count Total compatibility Start
                if ($nsExt['compatible4'] == 1) {
                    $totalCompatible4++;
                }
                if ($nsExt['compatible6'] == 1) {
                    $totalCompatible6++;
                }
                if ($nsExt['compatible7'] == 1) {
                    $totalCompatible7++;
                }
                if ($nsExt['compatible8'] == 1) {
                    $totalCompatible8++;
                }
                if ($nsExt['compatible9'] == 1) {
                    $totalCompatible9++;
                }
                if ($nsExt['compatible10'] == 1) {
                    $totalCompatible10++;
                }
                if ($nsExt['compatible11'] == 1) {
                    $totalCompatible11++;
                }
                if ($nsExt['installed'] == 1) {
                    $totalInstalled++;
                } else {
                    $totalNonInstalled++;
                }

                //Count Total compatibility End

                $extension = $nsExt;
            }
            //Filter all local extension for whole TER data end
        }
        $sysDetail = $this->getSysDetail();

        $sysDetail['targetVersion'] = $detailTargetVersion;
        $this->view->assign('sysDetail', $sysDetail);
        $this->view->assign('extension', $extension);
    }

    /**
     * This method is used for  get detail list of local extension
     */
    public function getAllExtensions($myTargetVersion)
    {
        $i = 1;
        $totalCompatible4 = 0;
        $totalCompatible6 = 0;
        $totalCompatible7 = 0;
        $totalCompatible8 = 0;
        $totalCompatible9 = 0;
        $totalCompatible10 = 0;
        $totalCompatible11 = 0;
        $totalInstalled = 0;
        $totalNonInstalled = 0;
        $assignArray = [];
        $extensionlist = [];
        $overviewReport = [];

        //Get extension list
        $myExtList = $this->objectManager->get(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();

        foreach ($allExtensions as $extensionKey => $nsExt) {
            //Filter all local extension for whole TER data start
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key'] != 'ns_ext_compatibility') {
                $newNsVersion = 0;
                $extArray = $this->extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);

                //Fetch typo3 depency of extesion  start
                if (count($extArray) != 0) {
                    foreach ($extArray as $extension) {
                        foreach ($extension->getDependencies() as $dependency) {
                            if ($dependency->getIdentifier() === 'typo3') {
                                // Extract min TYPO3 CMS version (lowest)
                                $minVersion = $dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion = $dependency->getHighestVersion();

                                if ($minVersion <= 7 && $maxVersion >= 6) {
                                    $nsExt['compatible6'] = 1;
                                }
                                if ($minVersion <= 8 && $maxVersion >= 7) {
                                    $nsExt['compatible7'] = 1;
                                }
                                if ($minVersion <= 9 && $maxVersion >= 8) {
                                    $nsExt['compatible8'] = 1;
                                }
                                if ($minVersion <= 10 && $maxVersion >= 9) {
                                    $nsExt['compatible9'] = 1;
                                }
                                if ($minVersion <= 11 && $maxVersion >= 10) {
                                    $nsExt['compatible10'] = 1;
                                }
                                if ($minVersion <= 12 && $maxVersion >= 11) {
                                    $nsExt['compatible11'] = 1;
                                }
                                if ((($maxVersion > (int) $myTargetVersion && $maxVersion <= (int) $myTargetVersion + 1) || $minVersion > (int) $myTargetVersion && $minVersion <= (int) $myTargetVersion + 1) && ($newNsVersion < $extension->getVersion())) {
                                    $newNsVersion = $extension->getVersion();
                                    $nsExt['newVersion'] = $newNsVersion;
                                }
                            }
                        }
                    }
                } else {
                    $nsExt['customExt'] = true;
                }
                //Fetch typo3 depency of extesion  end

                // Set overview Report start

                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }

                //Count Total compatibility Start
                if ($nsExt['compatible4'] == 1) {
                    $totalCompatible4++;
                }
                if ($nsExt['compatible6'] == 1) {
                    $totalCompatible6++;
                }
                if ($nsExt['compatible7'] == 1) {
                    $totalCompatible7++;
                }
                if ($nsExt['compatible8'] == 1) {
                    $totalCompatible8++;
                }
                if ($nsExt['compatible9'] == 1) {
                    $totalCompatible9++;
                }
                if ($nsExt['compatible10'] == 1) {
                    $totalCompatible10++;
                }
                if ($nsExt['compatible11'] == 1) {
                    $totalCompatible11++;
                }
                if ($nsExt['installed'] == 1) {
                    $totalInstalled++;
                } else {
                    $totalNonInstalled++;
                }
                //Count Total compatibility End

                // Set overview Report end
                $extensionlist[$i] = $nsExt;
                $i++;
            }
            //Filter all local extension for whole TER data end
        }
        //Set overview array start
        $overviewReport['totalInstalled'] = $totalInstalled;
        $overviewReport['totalNonInstalled'] = $totalNonInstalled;
        $overviewReport['totalCompatible6'] = $totalCompatible6;
        $overviewReport['totalCompatible7'] = $totalCompatible7;
        $overviewReport['totalCompatible8'] = $totalCompatible8;
        $overviewReport['totalCompatible9'] = $totalCompatible9;
        $overviewReport['totalCompatible10'] = $totalCompatible10;
        $overviewReport['totalCompatible11'] = $totalCompatible11;
        //Set overview array end

        $assignArray['overviewReport'] = $overviewReport;
        $assignArray['extensionlist'] = $extensionlist;

        return $assignArray;
    }

    /**
     * This method is used of get sysimformation
     */
    public function getSysDetail()
    {
        $sysDetail = [];
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ns_ext_compatibility'];
        } else {
            $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_ext_compatibility']);
        }
        $sysDetail['phpversion'] = substr(phpversion(), 0, 6);
        $sysDetail['targetVersion'] = $extConfig['typo3TargetVersion'];
        $sysDetail['sitename'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $sysDetail['typo3version'] = VersionNumberUtility::getNumericTypo3Version();
        $sysDetail['totalPages'] = $this->NsExtCompatibilityRepository->countPages();
        if (version_compare(TYPO3_branch, '10', '<')) {
            $sysDetail['totalDomain'] = $this->NsExtCompatibilityRepository->countDomain();
        }
        $sysDetail['totalLang'] = $this->NsExtCompatibilityRepository->sysLang();

        return $sysDetail;
    }

    /**
     * This method is used for get System requirement for target typo3 version
     **/
    public function getSysRequirementForTargetVersion($targetVersion)
    {
        try {
            exec('convert -version', $imgmagic);
            preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec('mysql -V'), $mysqlVersion);
        } catch (\Exception $e) {
            if (version_compare(TYPO3_branch, '6.2', '<')) {
                $erorrMessage = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $e->getMessage(),
                    'Exception: ' . $e->getCode(),  // the header is optional
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );

                \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($erorrMessage);
            } else {
                $this->addFlashMessage($e->getMessage(), 'Exception: ' . $e->getCode(), \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
            }
        }

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
            return Localize::translate($key, 'ns_ext_compatibility', $arguments);
        } else {
            return Localize::translate($key, 'ns_ext_compatibility');
        }
    }
}
