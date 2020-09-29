<?php
namespace NITSAN\NsExtCompatibility\Controller;

use FPDF;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;

class PDF extends FPDF
{
    // Page header
    public function Header()
    {
        $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_ext_compatibility']);
        $sitename = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $header = Localize::translate('typo3upgrad1', 'ns_ext_compatibility', ['sitename' => $sitename]);
        // Logo
        $this->Image($extConfig['pdflogo'], 10, 8, 50);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(60);
        // Title
        $this->MultiCell(190, 6, $header, 0, 'L', false);
        // Line break
        $this->Ln(15);
    }

    // System Details Table
    public function systemDetailsTable($sysDetail = null, $typo3Data = null)
    {
        if ($typo3Data['versionType']=='newVersionSecurityRelevant') {
            $this->SetFont('', 'B', 12);
            $this->MultiCell(250, 10, $typo3Data['versionInfo'], 0, 'L', false);
        } elseif ($typo3Data['versionInfo']=='newVersion') {
            $this->SetFont('', 'B', 12);
            $this->MultiCell(250, 10, $typo3Data['versionInfo'], 0, 'L', false);
        } elseif (isset($typo3Data['versionType'])) {
            $this->SetFont('', 'B', 12);
            $this->MultiCell(250, 10, $typo3Data['versionInfo'], 0, 'L', false);
        }

        if ($typo3Data['installedVersion']<$typo3Data['ltsVersion']) {
            $this->SetFont('', 'B', 12);
            $this->MultiCell(250, 10, $this->translate('t3-ltsVersion', ['s' => $typo3Data['ltsVersion']]), 0, 'L', false);
            $this->Ln(5);
        }

        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        // $fill = false;
        $this->SetFont('', 'B', 10);
        $this->Cell(60, 8, $this->translate('langague'), 1, 0, 'C', true);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['totalLang'], 1, 0, 'C', true);
        $this->Ln();

        $this->SetFont('', 'B');
        $this->Cell(60, 8, $this->translate('phpVersion'), 1, 0, 'C', false);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['phpversion'], 1, 0, 'C', false);
        $this->Ln();

        $this->SetFont('', 'B');
        $this->Cell(60, 8, $this->translate('currentTypo3Version'), 1, 0, 'C', true);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['typo3version'], 1, 0, 'C', true);
        $this->Ln();

        $this->SetFont('', 'B');
        $this->Cell(60, 8, $this->translate('targetTypo3Version'), 1, 0, 'C', false);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['targetVersion'], 1, 0, 'C', false);
        $this->Ln();

        $this->SetFont('', 'B');
        $this->Cell(60, 8, $this->translate('totalPagesofSite'), 1, 0, 'C', true);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['totalPages'], 1, 0, 'C', true);
        $this->Ln();

        $this->SetFont('', 'B');
        $this->Cell(60, 8, $this->translate('numberOfDomain'), 1, 0, 'C', false);
        $this->SetFont('');
        $this->Cell(60, 8, $sysDetail['totalDomain'], 1, 0, 'C', false);
        $this->Ln(20);
    }

    // System Extensions Table
    public function systemExtTable($extensionlist = null, $sysDetail = null, $overviewReport = null)
    {
        // Colors, line width and bold font
        $this->SetFont('', 'B', 10);
        $this->Cell(15, 7, $this->translate('srNo'), 1, 0, 'C', true);
        $this->Cell(45, 7, $this->translate('title'), 1, 0, 'C', true);
        $this->Cell(30, 7, $this->translate('extkey'), 1, 0, 'C', true);
        $this->Cell(18, 7, $this->translate('action'), 1, 0, 'C', true);
        if ($sysDetail['typo3version'] < 7 && $sysDetail['targetVersion'] >= 6) {
            $this->Cell(20, 7, $this->translate('compatible6'), 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 8 && $sysDetail['targetVersion'] >= 7) {
            $this->Cell(20, 7, $this->translate('compatible7'), 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 9 && $sysDetail['targetVersion'] >= 8) {
            $this->Cell(20, 7, $this->translate('compatible8'), 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 10 && $sysDetail['targetVersion'] >= 9) {
            $this->Cell(20, 7, $this->translate('compatible9'), 1, 0, 'C', true);
        }
        $this->Cell(22, 7, $this->translate('currentVersion'), 1, 0, 'C', true);
        $this->Cell(22, 7, $this->translate('newVersion'), 1, 0, 'C', true);
        $this->Cell(20, 7, $this->translate('state'), 1, 0, 'C', true);
        $this->Cell(20, 7, $this->translate('type'), 1, 0, 'C', true);
        $this->Ln();

        $k = 1;
        $this->SetFont('', '', 8);
        foreach ($extensionlist as $key => $extension) {
            if ($extension['key'] != 'ns_ext_compatibility') {
                $this->Cell(15, 7, $k, 1, 0, 'C', false);
                $this->Cell(45, 7, (strlen($extension['title']) > 25) ? substr($extension['title'], 0, 25) . '...' : $extension['title'], 1, 'L');
                $this->Cell(30, 7, (strlen($extension['key']) > 20) ? substr($extension['key'], 0, 20) . '...' : $extension['key'], 1, 'L');
                if ($extension['installed'] == 1) {
                    $this->SetTextColor(0, 115, 0);
                    $this->Cell(18, 7, $this->translate('yes'), 1, 0, 'C', false);
                } else {
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell(18, 7, $this->translate('no'), 1, 0, 'C', false);
                }
                if ($sysDetail['typo3version'] < 7 && $sysDetail['targetVersion'] >= 6) {
                    if ($extension['compatible6'] == 1) {
                        $this->SetTextColor(0, 115, 0);
                        $this->Cell(20, 7, $this->translate('yes'), 1, 0, 'C', false);
                    } else {
                        $this->SetTextColor(255, 0, 0);
                        $this->Cell(20, 7, $this->translate('no'), 1, 0, 'C', false);
                    }
                }
                if ($sysDetail['typo3version'] < 8 && $sysDetail['targetVersion'] >= 7) {
                    if ($extension['compatible7'] == 1) {
                        $this->SetTextColor(0, 115, 0);
                        $this->Cell(20, 7, $this->translate('yes'), 1, 0, 'C', false);
                    } else {
                        $this->SetTextColor(255, 0, 0);
                        $this->Cell(20, 7, $this->translate('no'), 1, 0, 'C', false);
                    }
                }
                if ($sysDetail['typo3version'] < 9 && $sysDetail['targetVersion'] >= 8) {
                    if ($extension['compatible8'] == 1) {
                        $this->SetTextColor(0, 115, 0);
                        $this->Cell(20, 7, $this->translate('yes'), 1, 0, 'C', false);
                    } else {
                        $this->SetTextColor(255, 0, 0);
                        $this->Cell(20, 7, $this->translate('no'), 1, 0, 'C', false);
                    }
                }
                if ($sysDetail['typo3version'] < 10 && $sysDetail['targetVersion'] >= 9) {
                    if ($extension['compatible9'] == 1) {
                        $this->SetTextColor(0, 115, 0);
                        $this->Cell(20, 7, $this->translate('yes'), 1, 0, 'C', false);
                    } else {
                        $this->SetTextColor(255, 0, 0);
                        $this->Cell(20, 7, $this->translate('no'), 1, 0, 'C', false);
                    }
                }
                $this->SetTextColor(0, 0, 0);
                $this->Cell(22, 7, $extension['version'], 1, 0, 'C', false);
                if ($extension['newVersion'] > $extension['version']) {
                    $this->SetTextColor(0, 115, 0);
                    $this->Cell(22, 7, $extension['newVersion'], 1, 0, 'C', false);
                } else {
                    if ($extension['updateToVersion'] != null) {
                        $this->SetTextColor(0, 115, 0);
                        $this->Cell(22, 7, $extension['updateToVersion'], 1, 0, 'C', false);
                    } else {
                        $this->SetTextColor(0, 0, 0);
                        $this->Cell(22, 7, $extension['version'], 1, 0, 'C', false);
                    }
                }
                if ($extension['state'] == 'stable') {
                    $this->SetTextColor(0, 115, 0);
                    $this->Cell(20, 7, $extension['state'], 1, 0, 'C', false);
                } elseif ($extension['state'] == 'beta') {
                    $this->SetTextColor(153, 153, 0);
                    $this->Cell(20, 7, $extension['state'], 1, 0, 'C', false);
                } elseif ($extension['state'] == 'alpha') {
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell(20, 7, $extension['state'], 1, 0, 'C', false);
                }
                $this->SetTextColor(0, 0, 0);
                if ($extension['terObject'] != null) {
                    $this->Cell(20, 7, $this->translate('ter'), 1, 0, 'C', false);
                } else {
                    $this->Cell(20, 7, $this->translate('custom'), 1, 0, 'C', false);
                }
                $this->Ln();
                $k++;
            }
        }

        $this->SetFont('', 'B', 10);
        $this->Cell(90, 7, $this->translate('overviewReport'), 1, 0, 'C', true);
        $this->Cell(18, 7, $overviewReport['totalInstalled'], 1, 0, 'C', true);
        if ($sysDetail['typo3version'] < 7 && $sysDetail['targetVersion'] >= 6) {
            $this->Cell(20, 7, $overviewReport['totalCompatible6'], 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 8 && $sysDetail['targetVersion'] >= 7) {
            $this->Cell(20, 7, $overviewReport['totalCompatible7'], 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 9 && $sysDetail['targetVersion'] >= 8) {
            $this->Cell(20, 7, $overviewReport['totalCompatible8'], 1, 0, 'C', true);
        }
        if ($sysDetail['typo3version'] < 10 && $sysDetail['targetVersion'] >= 9) {
            $this->Cell(20, 7, $overviewReport['totalCompatible9'], 1, 0, 'C', true);
        }
        $this->Cell(84, 7, '', 1, 0, 'C', true);
        $this->Ln(20);
    }

    // Server Details Table
    public function ServerDetailsTable($targetSystemRequirement = null)
    {
        $this->SetFont('', 'B', 12);
        $this->Cell(150, 8, $this->translate('targetSystemRequirement'), 1, 0, 'C', true);
        $this->Ln();
        $this->SetFont('', 'B', 10);
        $this->Cell(70, 8, $this->translate('targetSystemRequirement.module'), 1, 0, 'L', true);
        $this->Cell(40, 8, $this->translate('targetSystemRequirement.current'), 1, 0, 'C', true);
        $this->Cell(40, 8, $this->translate('targetSystemRequirement.required'), 1, 0, 'C', true);
        $this->Ln();
        $this->SetFont('', '', 8);
        foreach ($targetSystemRequirement as $key => $module) {
            $this->Cell(70, 8, $this->translate('targetSystemRequirement.' . $key), 1, 0, 'L', false);
            $this->Cell(40, 8, $module['current'], 1, 0, 'C', false);
            $this->Cell(40, 8, $module['required'], 1, 0, 'C', false);
            $this->Ln();
        }
    }

    // Page footer
    public function Footer()
    {
        $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_ext_compatibility']);
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 10);
        // Copyright
        $this->Cell(120, 10, iconv('UTF-8', 'ISO-8859-1', '© ') . $extConfig['pdfcopyright'], 0, 'L', false);
        // Page number
        $this->Cell(0, 10, ' Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
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
