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

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Annotation\Inject as inject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Backend Controller
 * @extensionScannerIgnoreLine
 */
class NsExtCompatibilityController extends ActionController
{
    protected $extensionRepository;

    protected $remoteRegistry;

    protected $NsExtCompatibilityRepository;

    protected $currentVersion;

    /**
    * @var ListUtility
    */
    protected $listUtility;
    public function __construct(
        NsExtCompatibilityRepository $NsExtCompatibilityRepository,
        ExtensionRepository $extensionRepository,
        RemoteRegistry $remoteRegistry,
        ListUtility $listUtility,
        protected ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->NsExtCompatibilityRepository  = $NsExtCompatibilityRepository;
        $this->extensionRepository = $extensionRepository;
        $this->remoteRegistry = $remoteRegistry;
        $this->currentVersion = VersionNumberUtility::getCurrentTypo3Version();
        $this->listUtility = $listUtility;

    }

    /**
     * This method is used for a fetch list of local extension
     * @return ResponseInterface
     * @throws Exception
     */
    public function listAction(): ResponseInterface
    {
        $sysDetail = $this->getSysDetail();

        //Get typo3 target version from argument and set new target version start
        $arguments = $this->request->getArguments();

        if (isset($arguments['tx_nsextcompatibility_tools_nsextcompatibilitynsextcompatibility']['targetVersion'])) {
            $targetVersion = $arguments['tx_nsextcompatibility_tools_nsextcompatibilitynsextcompatibility']['targetVersion'];
            $sysDetail['targetVersion'] = $targetVersion;
        }

        $environment = GeneralUtility::makeInstance(Environment::class);
        if ($environment->isComposerMode()) {
            $asPerMode = 'warning.TERUpdateTextComposer';
        } else {
            $asPerMode = 'warning.TERUpdateText';
        }

        $currentTime = strtotime('-30 days');

        $this->remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
        $lastUpdate = null;
        if($this->remoteRegistry) {
            foreach ($this->remoteRegistry->getListableRemotes() as $remote) {
                if ($lastUpdate === null || $lastUpdate < $remote->getLastUpdate()) {
                    $lastUpdate = $remote->getLastUpdate();
                }
            }
            $lastUpdateTime = $lastUpdate->format('Y-m-d');

            if (date('Y-m-d', $currentTime) > $lastUpdateTime) {
                $this->addFlashMessage($this->translate($asPerMode, ['date' => $lastUpdateTime]), $this->translate('warning.TERUpdateHeadline'), ContextualFeedbackSeverity::WARNING);
            }
        }

         if ((int)$sysDetail['targetVersion'] < $sysDetail['typo3version']) {
             $this->addFlashMessage($this->translate('warning.selectProperTargetVersionText'), $this->translate('warning.selectProperTargetVersionHeadline'), ContextualFeedbackSeverity::WARNING);
         }

        //Check typo3 target version from extension settings end
        $targetSystemRequirement = $this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);

        $assignArray = $this->getAllExtensions($sysDetail['targetVersion']);
        $assignArray['sysDetail'] = $sysDetail;
        $assignArray['targetSystemRequirement'] = $targetSystemRequirement;
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple($assignArray);
        return $view->renderResponse();
    }


    /**
     * Shows all versions of a specific extension
     * @return ResponseInterface
     */
    public function detailAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $extKey = $arguments['extKey'];
        $detailTargetVersion = $arguments['targetVersion'];

        //Get extension list
        $allExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $extension = [];
        foreach ($allExtensions as $nsExt) {
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
                                if ($minVersion <= 12 && $maxVersion >= 11) {
                                    $nsExt['compatible11'] = 1;
                                }
                                if ($minVersion <= 13 && $maxVersion >= 12) {
                                    $nsExt['compatible12'] = 1;
                                }
                                if ($minVersion <= 14 && $maxVersion >= 13) {
                                    $nsExt['compatible13'] = 1;
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

                // Set overview Report start
                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }
                if ($extArray[0]) {
                    $nsExt['newUplaodComment'] = $extArray[0]->getUpdateComment();
                    $nsExt['newLastDate'] = $extArray[0]->getLastUpdated();
                    $nsExt['newAlldownloadcounter'] = $extArray[0]->getAlldownloadcounter();
                }

                //Count Total compatibility End
                $extension = $nsExt;
            }
        }
        $sysDetail = $this->getSysDetail();
        $sysDetail['targetVersion'] = $detailTargetVersion;
        $this->view->assign('sysDetail', $sysDetail);
        $this->view->assign('extension', $extension);
        return $this->htmlResponse();
    }

    /**
     * This method is used for get a detail list of a local extension
     */
    public function getAllExtensions($myTargetVersion)
    {
        $i = 1;
        $totalCompatible12 = 0;
        $totalCompatible13 = 0;
        $totalInstalled = 0;
        $totalNonInstalled = 0;
        $assignArray = [];
        $extensionlist = [];
        $overviewReport = [];

        //Get han extension list
        $allExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();

        foreach ($allExtensions as $nsExt) {


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

                                if ($minVersion <= 12 && $maxVersion >= 11) {
                                    $nsExt['compatible11'] = 1;
                                }
                                if ($minVersion <= 13 && $maxVersion >= 12) {
                                    $nsExt['compatible12'] = 1;
                                }
                                if ($minVersion <= 14 && $maxVersion >= 13) {
                                    $nsExt['compatible13'] = 1;
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
                // Set overview Report start

                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }

                //Count Total compatibility Start
                if (isset($nsExt['compatible12']) and $nsExt['compatible12'] == 1) {
                    $totalCompatible12++;
                }
                if (isset($nsExt['compatible13']) and $nsExt['compatible13'] == 1) {
                    $totalCompatible13++;

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
        $overviewReport['totalCompatible12'] = $totalCompatible12;
        $overviewReport['totalCompatible13'] = $totalCompatible13;

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
        $extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ns_ext_compatibility'];
        $sysDetail['phpversion'] = substr(phpversion(), 0, 6);
        $sysDetail['targetVersion'] = $extConfig['typo3TargetVersion'];
        $sysDetail['sitename'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $sysDetail['typo3version'] = VersionNumberUtility::getNumericTypo3Version();
        $sysDetail['totalPages'] = $this->NsExtCompatibilityRepository->countPages();
        $sysDetail['totalDomain'] = $this->NsExtCompatibilityRepository->countDomain();
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
        $mysqlVersion = '';
        try {
            if ((int)VersionNumberUtility::getNumericTypo3Version() > 7) {
                list($mysqlVersion) = $this->getMysqlVersion();
            } else {
                exec('convert -version', $imgmagic);
                preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec('mysql -V'), $mysqlVersion);
            }
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), 'Exception: ' . $e->getCode(), \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
        }
        $typo3Config = [
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
            '13.x' => [
                'php' => [
                    'required' => '8.2',
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
    protected function initializeModuleTemplate(
        ServerRequestInterface $request
    ): ModuleTemplate {
        return $this->moduleTemplateFactory->create($request);
    }
}
