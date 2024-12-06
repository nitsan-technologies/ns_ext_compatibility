<?php

namespace  NITSAN\NsExtCompatibility\Domain\Repository;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
 * @extensionScannerIgnoreLine
 */

class NsExtCompatibilityRepository extends Repository
{
    /**
        * @var string
        */
    protected string $currentVersion;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->currentVersion = VersionNumberUtility::getCurrentTypo3Version();
    }


    /*
     * This method is used for get all pages of site
    */
    /**
     * @throws Exception
     */
    public function countPages()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->count('uid')
            ->from('pages')
            ->executeQuery()
            ->fetchOne();
    }

    /*
     * This method is used for get all domains of site
    */
    /**
     * @throws Exception
     */
    public function countDomain()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
        $totalDomain = $queryBuilder
            ->count('pid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot', 1)
            )
            ->groupBy('pid')
            ->executeQuery()
            ->fetchOne();
        if ($totalDomain > 0) {
            return $totalDomain;
        } else {
            return 1;
        }
    }

    /*
     * This method is used for get all system language of site
    */
    public function sysLang(): int
    {
        $allSiteConfiguration = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $uniqueArray = [];

        foreach ($allSiteConfiguration as $site) {
            foreach ($site->getAllLanguages() as $lang) {
                // @extensionScannerIgnoreLine
                $uniqueArray[] = $lang->getLanguageId();
            }
        }

        $uniqueArray = array_unique($uniqueArray);

        return count($uniqueArray) ?? 1;
    }

    public function getDBVersion()
    {
        $serverVersion = '';
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            try {
                $serverVersion = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (\Exception $exception) {}
        }
        return $serverVersion;
    }

}
