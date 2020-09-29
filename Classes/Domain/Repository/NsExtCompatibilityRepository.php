<?php
namespace  NITSAN\NsExtCompatibility\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class NsExtCompatibilityRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /*
     * This method is used for get all pages of site
    */
    public function countPages()
    {
        if (version_compare(TYPO3_branch, '9.0', '<')) {
            $totolPages= $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'pages', 'deleted=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
            $totolPages = $queryBuilder
                       ->count('uid')
                       ->from('pages')
                       ->execute()
                       ->fetchColumn(0);
        }
        return $totolPages;
    }

    /*
     * This method is used for get all domains of site
    */
    public function countDomain()
    {
        if (version_compare(TYPO3_branch, '9.0', '<')) {
            $totalDomain = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('DISTINCT pid', 'sys_domain', 'hidden=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('sys_domain');
            $totalDomain = $queryBuilder
                        ->count('pid')
                        ->from('sys_domain')
                        ->groupBy('pid')
                        ->execute()
                        ->fetchColumn(0);
        }
        if ($totalDomain>0) {
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
        if (version_compare(TYPO3_branch, '9.0', '<')) {
            $totalLang = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_language', 'hidden=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');
            $totalLang = $queryBuilder
                       ->count('uid')
                       ->from('sys_language')
                       ->execute()
                       ->fetchColumn(0);
        }
        return $totalLang+1;
    }
}
