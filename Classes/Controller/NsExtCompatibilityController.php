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

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository;
use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;


/**
 * Backend Controller
 */
class NsExtCompatibilityController extends ActionController
{
    protected $NsExtCompatibilityRepository;
    protected $extensionRepository;
    protected $remoteRegistry;
    protected $downloadBaseUri;
    protected $currentVersion;
    public function __construct(
        NsExtCompatibilityRepository $NsExtCompatibilityRepository,
        ExtensionRepository $extensionRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->NsExtCompatibilityRepository  = $NsExtCompatibilityRepository;
        $this->extensionRepository = $extensionRepository;
        $this->currentVersion = VersionNumberUtility::getCurrentTypo3Version();

    }


    /**
     * This method is used for fetch list of local extension
     * @return ResponseInterface
     * @throws RemoteFetchException
     */
    public function listAction(): ResponseInterface
    {
        $downloadBaseUri = 'https://get.typo3.org/';
        $url = $downloadBaseUri . 'json';
        $versionJson = GeneralUtility::getUrl($url);
        $ltsVersion = json_decode($versionJson, true);
        $latest_lts = $ltsVersion['latest_lts'];
        $versionNew = '';

        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);
        // No updates for development versions
        if (!$coreVersionService->isInstalledVersionAReleasedVersion()) {
            $versionType = 'isDevelopmentVersion';
        }

        $isUpdateAvailable = '';
        $isMaintainedVersion = '';
        try {
            $isUpdateAvailable = $this->isYoungerPatchReleaseAvailable($coreVersionService);
            $isMaintainedVersion = $this->isVersionActivelyMaintained($coreVersionService);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), 'Exception: ' . $e->getCode(), ContextualFeedbackSeverity::ERROR);
        }

        if (!$isUpdateAvailable && $isMaintainedVersion) {
            // Everything is fine, working with the latest version
            $versionType = 'uptodate';
        } elseif ($isUpdateAvailable) {
            // There is an update available
            $newVersion = $coreVersionService->getYoungestPatchRelease();
            $versionNew = $newVersion->getVersion();
            if ($coreVersionService->isUpdateSecurityRelevant($newVersion)) {
                $versionType = 'newVersionSecurityRelevant';
            } else {
                $versionType = 'newVersion';
            }
        } else {
            // Version is not maintained
            $versionType = 'versionOutdated';
        }

        $sysDetail = $this->getSysDetail();
        //Get typo3 target version from argument and set new target version start
        $arguments = $this->request->getArguments();
        if (isset($arguments['tx_nsextcompatibility_tools_nsextcompatibilitynsextcompatibility']['targetVersion'])) {
            $targetVersion = $arguments['tx_nsextcompatibility_tools_nsextcompatibilitynsextcompatibility']['targetVersion'];
            $sysDetail['targetVersion'] = $targetVersion;
        }

        //Waning Message as per typo3 installation mode
        $environment = GeneralUtility::makeInstance(Environment::class);
        $asPerMode = 'warning.TERUpdateText';
        if ($environment->isComposerMode()) {
            $asPerMode = 'warning.TERUpdateTextComposer';
        }

        $currentTime = strtotime('-30 days');
        //Get typo3 target version from argument and set new target version end

        $this->remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
        $lastUpdate = null;
        if ($this->remoteRegistry) {
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

        //Check typo3 target version from extension settings start
        if ((int)$sysDetail['targetVersion'] < (int)$sysDetail['typo3version']) {
            $this->addFlashMessage($this->translate('warning.selectProperTargetVersionText'), $this->translate('warning.selectProperTargetVersionHeadline'), ContextualFeedbackSeverity::WARNING);
        }

        //Check typo3 target version from extension settings end
        $targetSystemRequirement = $this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);
          foreach ($targetSystemRequirement as $name => &$module) {
        if (isset($module['required']) && is_array($module['required'])) {
            $module['required'] = implode(', ', $module['required']);
        }
        if (isset($module['current']) && is_array($module['current'])) {
            $module['current'] = implode(', ', $module['current']);
        }
    }
    unset($module);
        //call getAllExtensions() method for fetch extension list
        $assignArray = $this->getAllExtensions($sysDetail['targetVersion']);
        $assignArray['sysDetail'] = $sysDetail;
        $assignArray['targetSystemRequirement'] = $targetSystemRequirement;
        $assignArray['ltsVersion'] = $latest_lts;
        $assignArray['installedVersion'] = $this->currentVersion;
        $assignArray['versionType'] = $versionType;
        $assignArray['newVersion'] = $versionNew;

        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple($assignArray);
        return $view->renderResponse("NsExtCompatibility/List");
    }

    /*
     * This method is used for fetch all version of passed extension
     * * @return ResponseInterface
     */
    public function viewAllVersionAction(): ResponseInterface
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
                    // Extract max TYPO3 CMS version (highers)
                    $maxVersion = $dependency->getHighestVersion();
                    if ((($maxVersion > (int)$nsTargetVersion && $maxVersion <= (int)$nsTargetVersion + 1) || $minVersion > (int)$nsTargetVersion && $minVersion <= (int)$nsTargetVersion + 1) && ($newNsVersion < $extension->getVersion())) {
                        $compatVersion = $extension;
                    }
                }
            }
        }
        if (empty($compatVersion)) {
            $compatVersion = $allVersions[0];
        }

        $sysDetail['typo3version'] = VersionNumberUtility::getNumericTypo3Version();
        $allVersionRecords = [
            'sysDetail' => $sysDetail,
            'compatVersion' => $compatVersion,
            'allVersions' => $allVersions
        ];

        $this->view->assignMultiple($allVersionRecords);
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($allVersionRecords,__FILE__.''.__LINE__);die;
        return $this->htmlResponse();
    }

    /**
     * Shows all versions of a specific extension
     *
     * @return ResponseInterface
     */
    public function detailAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $extKey = $arguments['extKey'];
        $detailTargetVersion = $arguments['targetVersion'];
        //Get an extension list
        $myExtList = GeneralUtility::makeInstance(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $extension = '';
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
                                $minVersion = (int)$dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion = (int)$dependency->getHighestVersion();

                                for ($i = 6; $i <= 13; $i++) {
                                    if ($minVersion <= $i+1 && $maxVersion >= $i) {
                                        $nsExt['compatible' . $i] = 1;
                                    }
                                }

                                if ((($maxVersion > (int)$detailTargetVersion && $maxVersion <= (int)$detailTargetVersion + 1) || $minVersion > (int)$detailTargetVersion && $minVersion <= (int)$detailTargetVersion + 1) && ($newNsVersion < $extension->getVersion())) {
                                    $newNsVersion = $extension->getVersion();
                                    $nsExt['newVersion'] = $newNsVersion;
                                }
                            }
                        }
                    }
                } else {
                    $nsExt['customExt'] = true;
                }
                //Fetch typo3 dependency of the extesion  end

                // Set overview Report start
                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }
                if ($extArray[0]) {
                    $nsExt['newUplaodComment'] = $extArray[0]->getUpdateComment();
                    $nsExt['newLastDate'] = $extArray[0]->getLastUpdated();
                    $nsExt['newAlldownloadcounter'] = $extArray[0]->getAlldownloadcounter();
                }

                $extension = $nsExt;
            }
            //Filter all local extensions for whole TER data end
        }
        $sysDetail = $this->getSysDetail();

        $sysDetail['targetVersion'] = $detailTargetVersion;
        $allExtensionsData = [
            'sysDetail' => $sysDetail,
            'extension' => $extension,
        ];
        $this->view->assignMultiple($allExtensionsData);
        return $this->htmlResponse();
    }

    /**
     * This extension is used for export extension report
     * @throws RemoteFetchException
     */
    public function exportXlsAction(): void
    {
        $downloadBaseUri = 'https://get.typo3.org/';
        $url = $downloadBaseUri . 'json';
        $versionJson = GeneralUtility::getUrl($url);
        $ltsVersion = json_decode($versionJson, true);

        $typo3Data['ltsVersion'] = $ltsVersion['latest_stable'];
        $typo3Data['installedVersion'] = $this->currentVersion;

        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);

        // No updates for development versions
        if (!$coreVersionService->isInstalledVersionAReleasedVersion()) {
            $typo3Data['versionType'] = 'isDevelopmentVersion';
            $typo3Data['versionInfo'] = $this->translate('t3-' . $typo3Data['versionType']);
        }

        $isUpdateAvailable = '';
        $isMaintainedVersion = '';
        try {
            $isUpdateAvailable = $this->isYoungerPatchReleaseAvailable($coreVersionService);
            $isMaintainedVersion = $this->isVersionActivelyMaintained($coreVersionService);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), 'Exception: ' . $e->getCode(), ContextualFeedbackSeverity::ERROR);
        }

        if (!$isUpdateAvailable && $isMaintainedVersion) {
            // Everything is fine, working with the latest version
            $typo3Data['versionType'] = 'uptodate';
            $typo3Data['versionInfo'] = $this->translate('t3-' . $typo3Data['versionType']);
        } elseif ($isUpdateAvailable) {
            // There is an update available
            $newVersion = $coreVersionService->getYoungestPatchRelease();
            $typo3Data['newVersion'] = $newVersion->getVersion();
            $typo3Data['versionType'] = 'newVersion';
            if ($coreVersionService->isUpdateSecurityRelevant($newVersion)) {
                $typo3Data['versionType'] = 'newVersionSecurityRelevant';
            }
            $typo3Data['versionInfo'] = $this->translate('t3-' . $typo3Data['versionType']) . ' ' . $typo3Data['newVersion'];
        } else {
            // Version is not maintained
            $typo3Data['versionType'] = 'versionOutdated';
        }


        $arguments = $this->request->getArguments();
        $targetVersion = $arguments['targetVersion'];

        $assignArray = $this->getAllExtensions($targetVersion);
        $extensionlist = $assignArray['extensionlist'];
        $overviewReport = $assignArray['overviewReport'];

        $sysDetail = $this->getSysDetail();
        if (isset($targetVersion)) {
            $sysDetail['targetVersion'] = $targetVersion;
        }

        //Get system requirement for targetversion
        $targetSystemRequirement = $this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);

        //All Styles End
        $filename = strtolower(trim(preg_replace('#\W+#', '-', $sysDetail['sitename']), '_'));

        // Instanciation of inherited class
        $pdf = new PDF();

        $pdf->AliasNbPages();
        $pdf->AddPage('L');
        $pdf->systemDetailsTable($sysDetail, $typo3Data);
        $pdf->systemExtTable($extensionlist, $sysDetail, $overviewReport);
        $pdf->ServerDetailsTable($targetSystemRequirement);
        $pdf->Output('D', $this->translate('sheet.filename', ['sitename' => $filename]) . '.pdf');
        die();
    }

    /**
     * This method is used for  get detail list of local extension
     */
    public function getAllExtensions($myTargetVersion): array
    {
        $i = 1;
        $totalCompatible12 = 0;
        $totalCompatible13 = 0;
        $totalInstalled = 0;
        $totalNonInstalled = 0;
        $assignArray = [];
        $extensionlist = [];
        $overviewReport = [];

        //Get extension list
        $myExtList = GeneralUtility::makeInstance(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        foreach ($allExtensions as $nsExt) {
            //Filter all local extension for whole TER data start
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key'] != 'ns_ext_compatibility') {
                $newNsVersion = '0.0.0';
                $extArray = $this->extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);
                //Fetch typo3 depency of extesion  start
                if (count($extArray) != 0) {
                    foreach ($extArray as $extension) {
                        foreach ($extension->getDependencies() as $dependency) {
                            if ($dependency->getIdentifier() === 'typo3') {
                                // Extract min TYPO3 CMS version (lowest)
                                $minVersion = (int)$dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion = (int)$dependency->getHighestVersion();

                                if ($minVersion <= 12 && $maxVersion >= 12) {
                                    $nsExt['compatible12'] = 1;
                                }
                                if ($minVersion <= 13 && $maxVersion >= 13) {
                                    $nsExt['compatible13'] = 1;
                                }

                                if (version_compare($newNsVersion, $extension->getVersion(), '<')) {
                                    $newNsVersion = $extension->getVersion();
                                    $nsExt['newVersion'] = $newNsVersion;
                                    $nsExt['latestNew'] = '';
                                    if (version_compare($nsExt['newVersion'], $nsExt['version'], '>')) {
                                        $nsExt['latestNew'] = $nsExt['newVersion'];

                                    }
                                }
                            }
                        }
                    }
                } else {
                    $nsExt['customExt'] = true;
                }

                if ($extArray[0] && empty($nsExt['newVersion'])) {
                    $nsExt['newVersion'] = $extArray[0]->getVersion();
                }

                //Count Total compatibility Start
                if (isset($nsExt['compatible12']) && $nsExt['compatible12'] == 1) {
                    $totalCompatible12++;
                }
                if (isset($nsExt['compatible13']) && $nsExt['compatible13'] == 1) {
                    $totalCompatible13++;
                }
                if (isset($nsExt['installed']) && $nsExt['installed'] == 1) {
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

        //Check for a TYPO3 version below 10 and genrate documentation link

        $newExtensionList = [];
        foreach ($assignArray['extensionlist'] as $extensionListValue) {
            $key = $extensionListValue['key'];
            if (isset($key)) {
                list($newExtensionList) = $this->getNewExtensionList($key, $extensionListValue, '', $newExtensionList);
            }
        }
        $assignArray['extensionlist'] = $newExtensionList;

        return $assignArray;
    }

    /**
     * This method is used of get sysimformation
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSysDetail(): array
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
     **/
public function getSysRequirementForTargetVersion($targetVersion): array
{
    // Initialize mysqlVersion as empty string
    $mysqlVersionString = '';
    
    try {
        exec('convert -version', $imgmagic);
        if ((int)VersionNumberUtility::getNumericTypo3Version() > 7) {
            $mysqlVersionArray = $this->getMysqlVersion();
            // Extract string from array
            $mysqlVersionString = is_array($mysqlVersionArray) && isset($mysqlVersionArray[0]) 
                ? (string)$mysqlVersionArray[0] 
                : '';
        } else {
            preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec('mysql -V'), $matches);
            $mysqlVersionString = $matches[0] ?? '';
        }
    } catch (\Exception $e) {
        $this->addFlashMessage($e->getMessage(), 'Exception: ' . $e->getCode(), ContextualFeedbackSeverity::ERROR);
    }

    $versions = [
        '12.x' => ['php' => '8.1', 'mysql' => '8.0'],
        '13.x' => ['php' => '8.2', 'mysql' => '8.0']
    ];

    $typo3Config = [];
    foreach ($versions as $version => $requirements) {
        $typo3Config[$version] = [
            'php' => [
                'required' => $requirements['php'],
                'current' => substr(phpversion(), 0, 6),
            ],
            'mysql' => [
                'required' => $requirements['mysql'],
                'current' => $mysqlVersionString, // Now it's a string
            ],
            'imageMagick' => [
                'required' => explode('.', $version)[0] < 7 ? '-' : '6',
                'current' => isset($imgmagic[0]) ? substr($imgmagic[0], 21, 5) : '',
            ],
            'maxExecutionTime' => [
                'required' => '240',
                'current' => ini_get('max_execution_time'),
            ],
            'memoryLimit' => [
                'required' => '128M',
                'current' => ini_get('memory_limit'),
            ],
            'maxInputVars' => [
                'required' => '1500',
                'current' => ini_get('max_input_vars'),
            ],
            'uploadMaxSize' => [
                'required' => '200M',
                'current' => ini_get('upload_max_filesize'),
            ],
            'postMaxSize' => [
                'required' => '800M',
                'current' => ini_get('post_max_size'),
            ],
        ];
    }

    return $typo3Config[$targetVersion] ?? [];
}

    /**
     * @param string $key
     * @return null|string
     */
    protected function translate(string $key, $arguments = ''): ?string
    {
        if ($arguments != '') {
            return Localize::translate($key, 'ns_ext_compatibility', $arguments);
        } else {
            return Localize::translate($key, 'ns_ext_compatibility');
        }
    }

    /**
     * Returns TRUE if a younger patch level release exists in version matrix.
     *
     * @return bool TRUE if younger patch release is exists
     * @throws RemoteFetchException
     */
    public function isYoungerPatchReleaseAvailable($coreVersionService): bool
    {
        if (is_string($coreVersionService->getYoungestPatchRelease())) {
            return version_compare($coreVersionService->getInstalledVersion(), $coreVersionService->getYoungestPatchRelease()) === -1;
        }
        return version_compare($coreVersionService->getInstalledVersion(), $coreVersionService->getYoungestPatchRelease()->getVersion()) === -1;
    }

    /**
     * Checks if TYPO3 version (e.g. 6.2) is an actively maintained version
     *
     * @return bool TRUE if version is actively maintained
     * @throws RemoteFetchException
     */
    public function isVersionActivelyMaintained($coreVersionService): bool
    {
        $result = $coreVersionService->getMaintenanceWindow();

        return !isset($result->communitySupport) ||
            (
                new \DateTimeImmutable($result->communitySupport) >=
                new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
            );
    }

    /**
     *
     * @return array
     * @throws Exception
     */
    public function getMysqlVersion(): array
    {
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            try {
                $serverVersion = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (\Exception $exception) {
                $this->addFlashMessage($exception->getMessage(), 'Exception: ' . $exception->getCode(), ContextualFeedbackSeverity::ERROR);
            }
        }

        if (preg_match('/MySQL ([\d.]+)/', $serverVersion, $matches)) {
            $mysqlVersion = $matches[1]; // This will contain '10.4.33'
        } else {
            throw new Exception('Unable to extract MySQL version from string.');
        }
        return array($mysqlVersion);
    }

    /**
     * @param $key
     * @param $extensionListValue
     * @param $vendor
     * @param array $newExtensionList
     * @return array
     */
    private function getNewExtensionList($key, $extensionListValue, $vendor, array $newExtensionList): array
    {
        if (str_starts_with($key, 'ns_') || str_starts_with($key, 'nitsan_')) {
            if ($key !== 'ns_basetheme' && !str_starts_with($key, 'ns_theme_')) {
                $extensionListValue['link'] = ($key === 'ns_license') ?
                    'https://docs.t3planet.com/en/latest/License/Index.html' :
                    (($key === 'ns_t3ai') ?
                        'https://docs.t3planet.com/en/latest/ExtNsT3AI/Index.html' :
                        'https://docs.t3planet.com/en/latest/Ext' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . '/Index.html');
            }
        } else {
            $extensionListValue['vendor'] = $vendor;
        }
        $newExtensionList[] = $extensionListValue;
        return array($newExtensionList);
    }

    protected function initializeModuleTemplate(
        ServerRequestInterface $request
    ): ModuleTemplate {
        return $this->moduleTemplateFactory->create($request);
    }
}
