<?php

namespace  NITSAN\NsExtCompatibility\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

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

class NsExtCompatibilityRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
        * @var string
        */
    protected $currentVersion;

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
    public function countPages()
    {
        if (version_compare($this->currentVersion, '9.0', '<')) {
            $totolPages = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'pages', 'deleted=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
            $totolPages = $queryBuilder
                       ->count('uid')
                       ->from('pages')
                       ->executeQuery()
                       ->fetchOne();
        }
        return $totolPages;
    }

    /*
     * This method is used for get all domains of site
    */
    public function countDomain()
    {
        if (version_compare($this->currentVersion, '9.0', '<')) {
            $totalDomain = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('DISTINCT pid', 'sys_domain', 'hidden=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('sys_domain');
            $totalDomain = $queryBuilder
                        ->count('pid')
                        ->from('sys_domain')
                        ->groupBy('pid')
                        ->executeQuery()
                        ->fetchOne();
        }
        if ($totalDomain > 0) {
            return $totalDomain;
        } else {
            return 1;
        }
    }

    /*
     * This method is used for get all system language of site
    */
    public function sysLang()
    {
        if (version_compare($this->currentVersion, '9.0', '<')) {
            $totalLang = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_language', 'hidden=0');
        } else {
            //            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            //            ->getQueryBuilderForTable('sys_language');
            //            $totalLang = $queryBuilder
            //                       ->count('uid')
            //                       ->from('sys_language')
            //                       ->executeQuery()
            //                       ->fetchOne();
        }
        return 1;
    }

    public function getDBVersion()
    {
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            try {
                $serverVersion = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (\Exception $exception) {
            }
        }
        return $serverVersion;
    }

}
