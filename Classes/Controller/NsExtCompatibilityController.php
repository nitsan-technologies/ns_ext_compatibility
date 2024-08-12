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

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Extbase\Annotation\Inject as inject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;

/**
 * Backend Controller
 * @extensionScannerIgnoreLine
 */
class NsExtCompatibilityController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     * @extensionScannerIgnoreLine
     * @inject
     */
    protected $extensionRepository;

    /**
    * Inject extensionRepository object
    *
    * @param ExtensionRepository $extensionRepository object
    */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     * @extensionScannerIgnoreLine
     * @inject
     */
    protected $repositoryRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry
     */
    protected $remoteRegistry;

    /**
     * @var \NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository
     */
    protected $NsExtCompatibilityRepository;

    /**
     * Inject NsExtCompatibilityRepository object
     *
     * @param NsExtCompatibilityRepository $NsExtCompatibilityRepository object
     */
    public function injectResourceFactory(NsExtCompatibilityRepository $NsExtCompatibilityRepository)
    {
        $this->NsExtCompatibilityRepository = $NsExtCompatibilityRepository;
    }

    /**
     * This method is used for a fetch list of local extension
     */
    public function listAction()
    {
        $sysDetail = $this->getSysDetail();
        //Get typo3 target version from argument and set new target version start
        $arguments = $this->request->getArguments();
        if (isset($arguments['targetVersion'])) {
            $targetVersion = $arguments['targetVersion'];
            $sysDetail['targetVersion'] = $targetVersion;
        }
        //Get typo3 target version from argument and set new target version end
        $terRepo = null;

        //Waning Message as per typo3 installation mode
        if (version_compare(TYPO3_branch, '9', '<')) {
            $composerMode = file_exists(PATH_site . 'composer.json') || file_exists(PATH_site . 'composer.lock');
            if ($composerMode) {
                $asPerMode = 'warning.TERUpdateTextComposer';
            } else {
                $asPerMode = 'warning.TERUpdateText';
            }
        } else {
            $environment = GeneralUtility::makeInstance(Environment::class);
            if ($environment->isComposerMode()) {
                $asPerMode = 'warning.TERUpdateTextComposer';
            } else {
                $asPerMode = 'warning.TERUpdateText';
            }
        }

        $currentTime = strtotime('-30 days');
        if (version_compare(TYPO3_branch, '11', '<')) {
            $terRepo = $this->repositoryRepository->findOneTypo3OrgRepository();
            if ($terRepo != null) {
                $lastUpdatedTime = $terRepo->getLastUpdate();
                if (version_compare(TYPO3_branch, '6.2', '<')) {
                    if (date('Y-m-d', $currentTime) > $lastUpdatedTime->format('Y-m-d')) {
                        $TERUpdateMessage = GeneralUtility::makeInstance(
                            'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                            $this->translate($asPerMode, ['date' => $lastUpdatedTime->format('Y-m-d')]),
                            $this->translate('warning.TERUpdateHeadline'), // the header is optional
                            \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                        );

                        \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($TERUpdateMessage);
                    }
                } else {
                    if (date('Y-m-d', $currentTime) > $lastUpdatedTime->format('Y-m-d')) {
                        $this->addFlashMessage($this->translate($asPerMode, ['date' => $lastUpdatedTime->format('Y-m-d')]), $this->translate('warning.TERUpdateHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
                    }
                }
            }
        } else {
            $this->remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
            $lastUpdate = null;
            if($this->remoteRegistry) {
                foreach ($this->remoteRegistry->getListableRemotes() as $remote) {
                    if ($lastUpdate === null || $lastUpdate < $remote->getLastUpdate()) {
                        $lastUpdate = $remote->getLastUpdate();
                    }
                }
                $lastUpdateTime = $lastUpdate->format('Y-m-d');
                // if (date('Y-m-d', $currentTime) > $lastUpdateTime) {
                //     // $this->addFlashMessage($this->translate($asPerMode, ['date' => $lastUpdateTime]), $this->translate('warning.TERUpdateHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
                // }
            }
        }

        //Check typo3 target version from extension settings start
        if (version_compare(TYPO3_branch, '6.2', '<')) {
            if ($sysDetail['targetVersion'] < $sysDetail['typo3version']) {
                $selectProperTargetVersionMessage = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $this->translate('warning.selectProperTargetVersionText'),
                    $this->translate('warning.selectProperTargetVersionHeadline'), // the header is optional
                    // \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                );

                \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($selectProperTargetVersionMessage);
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
        $totalCompatible12 = 0;
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
                                if ($minVersion <= 13 && $maxVersion >= 12) {
                                    $nsExt['compatible12'] = 1;
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
                if (isset($nsExt['compatible4']) && $nsExt['compatible4'] == 1) {
                    $totalCompatible4++;
                }
                if (isset($nsExt['compatible6']) && $nsExt['compatible6'] == 1) {
                    $totalCompatible6++;
                }
                if (isset($nsExt['compatible7']) && $nsExt['compatible7'] == 1) {
                    $totalCompatible7++;
                }
                if (isset($nsExt['compatible8']) && $nsExt['compatible8'] == 1) {
                    $totalCompatible8++;
                }
                if (isset($nsExt['compatible9']) && $nsExt['compatible9'] == 1) {
                    $totalCompatible9++;
                }
                if (isset($nsExt['compatible10']) && $nsExt['compatible10'] == 1) {
                    $totalCompatible10++;
                }
                if (isset($nsExt['compatible11']) && $nsExt['compatible11'] == 1) {
                    $totalCompatible11++;
                }
                if (isset($nsExt['compatible12']) && $nsExt['compatible12'] == 1) {
                    $totalCompatible12++;
                }
                if (isset($nsExt['installed']) && $nsExt['installed'] == 1) {
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
     * This method is used for get a detail list of a local extension
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
        $totalCompatible12 = 0;
        $totalInstalled = 0;
        $totalNonInstalled = 0;
        $assignArray = [];
        $extensionlist = [];
        $overviewReport = [];

        //Get han extension list
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
                                $minVersion = (int) $dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion = (int) $dependency->getHighestVersion();

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
                                if ($minVersion <= 13 && $maxVersion >= 12) {
                                    $nsExt['compatible12'] = 1;
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
                if (isset($nsExt['compatible4']) and $nsExt['compatible4'] == 1) {
                    $totalCompatible4++;
                }
                if (isset($nsExt['compatible6']) and $nsExt['compatible6'] == 1) {
                    $totalCompatible6++;
                }
                if (isset($nsExt['compatible7']) and $nsExt['compatible7'] == 1) {
                    $totalCompatible7++;
                }
                if (isset($nsExt['compatible8']) and $nsExt['compatible8'] == 1) {
                    $totalCompatible8++;
                }
                if (isset($nsExt['compatible9']) and $nsExt['compatible9'] == 1) {
                    $totalCompatible9++;
                }
                if (isset($nsExt['compatible10']) and $nsExt['compatible10'] == 1) {
                    $totalCompatible10++;
                }
                if (isset($nsExt['compatible11']) and $nsExt['compatible11'] == 1) {
                    $totalCompatible11++;
                }
                if (isset($nsExt['compatible12']) and $nsExt['compatible12'] == 1) {
                    $totalCompatible12++;
                }
                if (isset($nsExt['installed']) and $nsExt['installed'] == 1) {
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
        $overviewReport['totalCompatible12'] = $totalCompatible12;

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
     *
     * @throws Exception
     */
    public function getSysRequirementForTargetVersion($targetVersion)
    {
        try {
            if ((int)VersionNumberUtility::getNumericTypo3Version() > 7) {
                list($mysqlVersion) = $this->getMysqlVersion();
            } else {
                exec('convert -version', $imgmagic);
                preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec('mysql -V'), $mysqlVersion);
            }
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
                    'current' => $mysqlVersion,
                ],
            ],
            '6.x' => [
                'php' => [
                    'required' => '5.3',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.1-5.6',
                    'current' => $mysqlVersion,
                ],
            ],
            '7.x' => [
                'php' => [
                    'required' => '5.5',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.5-5.7',
                    'current' => $mysqlVersion,
                ],
            ],
            '8.x' => [
                'php' => [
                    'required' => '7',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion,
                ],
            ],
            '9.x' => [
                'php' => [
                    'required' => '7.2',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion,
                ],
            ],
            '10.x' => [
                'php' => [
                    'required' => '7.2',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.0-5.7',
                    'current' => $mysqlVersion,
                ],
            ],
            '11.x' => [
                'php' => [
                    'required' => '7.4',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '5.7',
                    'current' => $mysqlVersion,
                ],
            ],
            '12.x' => [
                'php' => [
                    'required' => '8.1',
                    'current' => substr(phpversion(), 0, 6),
                ],
                'mysql' => [
                    'required' => '8.0',
                    'current' => $mysqlVersion,
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

    /**
     *
     * @return array
     * @throws Exception
     */
    public function getMysqlVersion(): array
    {
        $serverVersion = $this->NsExtCompatibilityRepository->getDBVersion();
        if (preg_match('/MySQL ([\d.]+)/', $serverVersion, $matches)) {
            $mysqlVersion = $matches[1]; // This will contain '10.4.33'
        } else {
            throw new Exception('Unable to extract MySQL version from string.');
        }
        return array($mysqlVersion);
    }
}
