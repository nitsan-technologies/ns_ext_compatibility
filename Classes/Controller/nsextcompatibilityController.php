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
 * @package ns_ext_compatibility
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */

use NITSAN\NsExtCompatibility\Utility\Extension;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;

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
     * @var NITSAN\NsExtCompatibility\Domain\Repository\NsExtCompatibilityRepository
     * @inject
    */
    protected $NsExtCompatibilityRepository;
   
   
    /**
     * This method is used for fetch list of local extension
    */
    public function listAction()
    {   

        $sysDetail=$this->getSysDetail();
        //Get typo3 target version from argument and set new target version start
        $arguments= $this->request->getArguments();
        $targetVersion=$arguments['targetVersion'];
        if(isset($targetVersion)){
            $sysDetail['targetVersion']=$targetVersion;
        }
        //Get typo3 target version from argument and set new target version end
        $terRepo=$this->repositoryRepository->findOneTypo3OrgRepository();
        //Check last updated Date and give  show warning start
         if($terRepo!=null){
            $lastUpdatedTime=$terRepo->getLastUpdate();
            $currentTime= strtotime('-30 days');
            if (version_compare(TYPO3_branch, '6.2', '<')) {
                if(date("Y-m-d",$currentTime)>$lastUpdatedTime->format('Y-m-d')){
                    $TERUpdateMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $this->translate('warning.TERUpdateText',array('date'=>$lastUpdatedTime->format('Y-m-d'))),
                    $this->translate('warning.TERUpdateHeadline'), // the header is optional
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
                    
                    \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($TERUpdateMessage);
                }

            }else{
                if(date("Y-m-d",$currentTime)>$lastUpdatedTime->format('Y-m-d')){
                     $this->addFlashMessage($this->translate('warning.TERUpdateText',array('date'=>$lastUpdatedTime->format('Y-m-d'))),$this->translate('warning.TERUpdateHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
                }
            }
        }
        //Check last updated Date and give  show warning end

        //Check typo3 target version from extension settings start
        if (version_compare(TYPO3_branch, '6.2', '<')) {
            if($sysDetail['targetVersion']< $sysDetail['typo3version']){
                $selectProperTargetVersionMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $this->translate('warning.selectProperTargetVersionText'),
                $this->translate('warning.selectProperTargetVersionHeadline'), // the header is optional
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
                
                \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($selectProperTargetVersionMessage);
            }
        }else{
            if($sysDetail['targetVersion']< $sysDetail['typo3version']){
                $this->addFlashMessage($this->translate('warning.selectProperTargetVersionText'),$this->translate('warning.selectProperTargetVersionHeadline'), \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
            }
        }
        //Check typo3 target version from extension settings end
        $targetSystemRequirement=$this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);

        //call getAllExtensions() method for fetch extension list
        $assignArray=$this->getAllExtensions($sysDetail['targetVersion']);

        $assignArray['sysDetail']=$sysDetail;
        $assignArray['targetSystemRequirement']=$targetSystemRequirement;
        $this->view->assignMultiple($assignArray);
       
    }

    /*
    * This method is used for fetch all version of passed extension
    */
    public function viewAllVersionAction(){
        $arguments= $this->request->getArguments();
        $extension=$arguments['extension'];
        $nsTargetVersion=$arguments['targetVersion'];
        $allVersions=$this->extensionRepository->findByExtensionKeyOrderedByVersion($extension);
        $newNsVersion=0;
        foreach ($allVersions as $extension) {
            foreach ($extension->getDependencies() as $dependency) {
                if ($dependency->getIdentifier() === 'typo3') {
                    // Extract min TYPO3 CMS version (lowest)
                    $minVersion= $dependency->getLowestVersion();
                    // Extract max TYPO3 CMS version (higherst)
                    $maxVersion=$dependency->getHighestVersion();
                    if((($maxVersion>(int)$nsTargetVersion && $maxVersion<=(int)$nsTargetVersion+1)|| $minVersion>(int)$nsTargetVersion && $minVersion<=(int)$nsTargetVersion+1) && ($newNsVersion<$extension->getVersion())){
                        $compatVersion=$extension;
                    }
                }
            }
        }
        if(empty($compatVersion)){
            $compatVersion=$allVersions[0];
        }
        $this->view->assign('compatVersion',$compatVersion);
        $this->view->assign('allVersions',$allVersions);
    }

     /**
     * Shows all versions of a specific extension
     *
     * @param string $extensionKey
     * @return void
    */
    public function detailAction(){
        $arguments= $this->request->getArguments();
        $extKey=$arguments['extKey'];
        $detailTargetVersion=$arguments['targetVersion'];
        //Get extension list
        $myExtList = $this->objectManager->get(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        foreach ($allExtensions as $extensionKey => $nsExt) {
            $newNsVersion=0;
            //Filter all local extension for whole TER data start
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key']==$extKey) {
                $extArray = $this->extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);  
                //Fetch typo3 depency of extesion  start
                if(count($extArray)!=0){
                    foreach ($extArray as $extension) {
                        foreach ($extension->getDependencies() as $dependency) {
                            if ($dependency->getIdentifier() === 'typo3') {
                                // Extract min TYPO3 CMS version (lowest)
                                $minVersion= $dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion=$dependency->getHighestVersion();
                                if($minVersion<=7 &&  $maxVersion>=6){
                                    $nsExt['compatible6']=1;
                                }
                                if($minVersion<=8 &&  $maxVersion>=7){
                                    $nsExt['compatible7']=1;
                                }
                                if($minVersion<=9 &&  $maxVersion>=8){
                                    $nsExt['compatible8']=1;
                                }
                                if($minVersion<=10 &&  $maxVersion>=9){
                                    $nsExt['compatible9']=1;
                                }
                                if((($maxVersion>(int)$detailTargetVersion && $maxVersion<=(int)$detailTargetVersion+1)|| $minVersion>(int)$detailTargetVersion && $minVersion<=(int)$detailTargetVersion+1) && ($newNsVersion<$extension->getVersion())){
                                    $newNsVersion=$extension->getVersion();
                                    $nsExt['newVersion']=$newNsVersion;
                                }
                            }
                        }
                    }
                }
                else{
                    //Check dependancy for custom extension start
                    foreach ($nsExt['constraints']['depends'] as $type => $customDepency) {
                        if($type=='typo3'){
                             $version=explode('-',$customDepency);
                            if(($version[0]<6 && $version[1]>=4) || ($version[0]<6 && $version[1]==0.0)){
                                $nsExt['compatible4']=1;
                            }
                            if($version[0]<=7 && $version[1]>=6){
                                $nsExt['compatible6']=1;
                            }
                            if($version[0]<=8 && $version[1]>=7){
                                $nsExt['compatible7']=1;
                            }
                            if($version[0]<=9 && $version[1]>=8){
                                $nsExt['compatible8']=1;
                            }
                            if($version[0]<=10 && $version[1]>=9){
                                $nsExt['compatible9']=1;
                            } 

                        }
                    }
                    //Check dependancy for custom extension end
                }
                //Fetch typo3 depency of extesion  end

                // Set overview Report start
                if($extArray[0] && empty($nsExt['newVersion'])){
                   $nsExt['newVersion']=$extArray[0]->getVersion();
                }
                if($extArray[0]){
                   
                    $nsExt['newUplaodComment']=$extArray[0]->getUpdateComment();
                    $nsExt['newLastDate']=$extArray[0]->getLastUpdated();
                    $nsExt['newAlldownloadcounter']=$extArray[0]->getAlldownloadcounter();
                }
                
                //Count Total compatibility Start
                    if($nsExt['compatible4']==1){
                        $totalCompatible4++;
                    }
                    if($nsExt['compatible6']==1){
                        $totalCompatible6++;
                    }
                    if($nsExt['compatible7']==1){
                        $totalCompatible7++;
                    }
                    if($nsExt['compatible8']==1){
                        $totalCompatible8++;
                    }
                    if($nsExt['compatible9']==1){
                        $totalCompatible9++;
                    }
                    if($nsExt['installed']==1){
                       $totalInstalled++;
                    }else{
                       $totalNonInstalled++;
                    }

                //Count Total compatibility End

                $extension=$nsExt;
            }
            //Filter all local extension for whole TER data end
  
        }
        $sysDetail=$this->getSysDetail();
       
        $sysDetail['targetVersion']=$detailTargetVersion;
        $this->view->assign('sysDetail',$sysDetail);
        $this->view->assign('extension',$extension);
    }
   

    /**
     * This extension is used for export extension report
    */
    public function exportXlsAction()
    {   

        $arguments= $this->request->getArguments();
        $targetVersion=$arguments['targetVersion'];

        //call getAllExtensions() method for fetch extension start
        $assignArray=$this->getAllExtensions($targetVersion);
        $extensionlist=$assignArray['extensionlist'];
        $overviewReport=$assignArray['overviewReport'];
        //call getAllExtensions() method for fetch extension end
        //call getSysDetail() method for fetch basic information of system
        $sysDetail=$this->getSysDetail();
        if(isset($targetVersion)){
            $sysDetail['targetVersion']=$targetVersion;
        }

        //Get system requirement for targetversion Start
        $targetSystemRequirement=$this->getSysRequirementForTargetVersion($sysDetail['targetVersion']);
        //Get system requirement for targetversion End


        //All Styles Start
        $mainstyle = array(
            'font' => array(
                'bold' => false,
                'name'  => 'Verdana'
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ),
        );    
        $overviewReportStyle = array(
            'font'  => array(
                'bold'  => false,
                'color' => array('rgb' => 'b32200'),
                'size'  => 14,
                'name'  => 'Verdana'
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'efefef')
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => 'cbcbcb')
                )
            )
        );
        $titleStyle = array(
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ),
        );
        $boldOnly = array(
            'font'  => array(
                'bold'  => false,
                'size'  => 14,
                'name'  => 'Verdana'
            ),
        );
        $projectTitle = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 24
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'f49700')
            ),
        );
        $sysTableStyle = array(
            'font'  => array(
                'bold'  => false,
                'size'  => 10,
                'name'  => 'Verdana'
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ),
        );

        $extTableHeadingStype = array(
            'font'  => array(
                'bold'  => false,
                'color' => array('rgb' => 'b32200'),
                'size'  => 12,
                'name'  => 'Verdana'
            ),
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'efefef')
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => 'cbcbcb')
                )
            )
        );
        $syleYes = array(
            'font'  => array(
                'color' => array('rgb' => '3db900'),
            ),
        );
        $syleNo = array(
            'font'  => array(
                'color' => array('rgb' => 'f14400'),
            ),
        );
        $syleBeta = array(
            'font'  => array(
                'color' => array('rgb' => 'f4bd00'),
            ),
        );
        //All Styles End
        $filename = strtolower(trim(preg_replace('#\W+#', '-', $sysDetail['sitename']), '_'));

        //Excel Basic Settings Start
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=".$this->translate('sheet.filename',array('sitename'=>$filename)).".xlsx"); 
        header("Pragma: no-cache"); 
        header("Expires: 0"); 
        $excel = $this->objectManager->get('PHPExcel');


        $excel->getProperties()->setCreator("ns_ext_compatibility")
                 ->setLastModifiedBy("ns_ext_compatibility")
                 ->setTitle($this->translate('sheet.filename',array('sitename'=>$filename)));
        $excel->setActiveSheetIndex(0);
       

        $excel->getDefaultStyle()->applyFromArray($mainstyle);
        //Excel Basic Settings End

        //Style of Project title start
        $excel->setActiveSheetIndex(0)->mergeCells('A2:M2');
        
        $excel->getActiveSheet()->getStyle('A2')->applyFromArray($projectTitle);
        $excel->getActiveSheet()->getRowDimension('2')->setRowHeight(30);
        //Style of Project title end

        //Rows of system detail Start
        $sysRow = $excel->setActiveSheetIndex(0);
        $sysRow->setCellValue("A2", $this->translate('typo3upgrade')."".$sysDetail['sitename']);
        $sysRow->setCellValue("B4",$this->translate('langague'));
        $sysRow->setCellValue("C4", $sysDetail['totalLang']);
        $sysRow->setCellValue("B5",$this->translate('phpVersion'));
        $sysRow->setCellValue("C5", $sysDetail['phpversion']);
        $sysRow->setCellValue("B6",$this->translate('currentTypo3Version'));
        $sysRow->setCellValue("C6", $sysDetail['typo3version']);
        $sysRow->setCellValue("B7",$this->translate('targetTypo3Version'));
        $sysRow->setCellValue("C7", $sysDetail['targetVersion']);
        $sysRow->setCellValue("B8", $this->translate('totalPagesofSite'));
        $sysRow->setCellValue("C8", $sysDetail['totalPages']);
        $sysRow->setCellValue("B9",$this->translate('numberOfDomain'));
        $sysRow->setCellValue("C9", $sysDetail['totalDomain']);
       
        $excel->getActiveSheet()->getStyle('B4:C9')->applyFromArray($sysTableStyle);
        //Rows of system detail End
       
        //Excel Extension Table heading style Start
        $extTableHeader = $excel->setActiveSheetIndex(0);
       
        $excel->getActiveSheet()->getRowDimension('11')->setRowHeight(30);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(24);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(13);
        $excel->getActiveSheet()->getColumnDimension('J')->setWidth(13);
        $excel->getActiveSheet()->getColumnDimension('K')->setWidth(7);
        $excel->getActiveSheet()->getColumnDimension('L')->setWidth(7);
        $excel->getActiveSheet()->getColumnDimension('M')->setWidth(30);
       
        $excel->getActiveSheet()->getStyle('A11:M11')->applyFromArray($extTableHeadingStype);
    
        //Excel Extension Table heading style End

        //Excel Extension Table heading Start
        $extTableHeader->setCellValue("A11",$this->translate('srNo'));
        $extTableHeader->setCellValue("B11",$this->translate('title'));
        $extTableHeader->setCellValue("C11",$this->translate('extkey'));
        $extTableHeader->setCellValue("D11",$this->translate('action'));
        $extTableHeader->setCellValue("E11",$this->translate('compatible6'));
        $extTableHeader->setCellValue("F11",$this->translate('compatible7'));
        $extTableHeader->setCellValue("G11",$this->translate('compatible8'));
        $extTableHeader->setCellValue("H11",$this->translate('compatible9'));
        $extTableHeader->setCellValue("I11",$this->translate('currentVersion'));
        $extTableHeader->setCellValue("J11",$this->translate('newVersion'));
        $extTableHeader->setCellValue("K11",$this->translate('state'));
        $extTableHeader->setCellValue("L11",$this->translate('type'));
        $extTableHeader->setCellValue("M11",$this->translate('notes'));
       
      
        //Hide Compatibility columns start
        if($sysDetail['typo3version']<7 && $sysDetail['targetVersion']>=6){
            $excel->getActiveSheet()->getColumnDimension('E')->setVisible(true);
        }else{
            $excel->getActiveSheet()->getColumnDimension('E')->setVisible(false);
        }
        if($sysDetail['typo3version']<8 && $sysDetail['targetVersion']>=7){
            $excel->getActiveSheet()->getColumnDimension('F')->setVisible(true);
        }else{
            $excel->getActiveSheet()->getColumnDimension('F')->setVisible(false);
        }
        if($sysDetail['typo3version']<9 && $sysDetail['targetVersion']>=8){
            $excel->getActiveSheet()->getColumnDimension('G')->setVisible(true);
        }else{
            $excel->getActiveSheet()->getColumnDimension('G')->setVisible(false);
        }
        if($sysDetail['typo3version']<10 && $sysDetail['targetVersion']>=9){
            $excel->getActiveSheet()->getColumnDimension('H')->setVisible(true);
        }else{
            $excel->getActiveSheet()->getColumnDimension('H')->setVisible(false);
        }
        //Hide Compatibility columns end

        //Excel Extension Table heading end
                    
       //Add Data in Excel From array $extensionlist end
        
        $i=12;
        $k=1;
        foreach ($extensionlist as $key=> $extension) {
            if($extension['key']!='ns_ext_compatibility'){
                $row = $excel->setActiveSheetIndex(0);
                $row->setCellValue("A$i",$k);
                $row->setCellValue("B$i",$extension['title']);
                $row->setCellValue("C$i",$extension['key']);
                 if($extension['installed']==1){
                   $row->setCellValue("D$i",$this->translate('yes'));
                   $excel->getActiveSheet()->getStyle("D$i")->applyFromArray($syleYes);
                }else{
                    $row->setCellValue("D$i",$this->translate('no'));
                    $excel->getActiveSheet()->getStyle("D$i")->applyFromArray($syleNo);
                }
                if($extension['compatible6']==1){
                    $row->setCellValue("E$i",$this->translate('yes'));
                    $excel->getActiveSheet()->getStyle("E$i")->applyFromArray($syleYes);
                }else{
                    $row->setCellValue("E$i",$this->translate('no'));
                    $excel->getActiveSheet()->getStyle("E$i")->applyFromArray($syleNo);
                }
                if($extension['compatible7']==1){
                    $row->setCellValue("F$i",$this->translate('yes'));
                    $excel->getActiveSheet()->getStyle("F$i")->applyFromArray($syleYes);
                }else{
                    $row->setCellValue("F$i",$this->translate('no'));
                    $excel->getActiveSheet()->getStyle("F$i")->applyFromArray($syleNo);
                }
                if($extension['compatible8']==1){
                    $row->setCellValue("G$i",$this->translate('yes'));
                    $excel->getActiveSheet()->getStyle("G$i")->applyFromArray($syleYes);
                }else{
                    $row->setCellValue("G$i",$this->translate('no'));
                    $excel->getActiveSheet()->getStyle("G$i")->applyFromArray($syleNo);
                }
                if($extension['compatible9']==1){
                    $row->setCellValue("H$i",$this->translate('yes'));
                    $excel->getActiveSheet()->getStyle("H$i")->applyFromArray($syleYes);
                }else{
                    $row->setCellValue("H$i",$this->translate('no'));
                    $excel->getActiveSheet()->getStyle("H$i")->applyFromArray($syleNo);
                }
               
                $row->setCellValue("I$i",$extension['version']);
                if($extension['newVersion']>$extension['version']){
                     $row->setCellValue("J$i",$extension['newVersion']);
                    $excel->getActiveSheet()->getStyle("J$i")->applyFromArray($syleYes);
                }else{
                    if($extension['updateToVersion']!=null){
                        $row->setCellValue("J$i",$extension['updateToVersion']->getVersion());
                        $excel->getActiveSheet()->getStyle("J$i")->applyFromArray($syleYes);
                    }else{
                        $row->setCellValue("J$i",$extension['version']);
                    }
                }
                $row->setCellValue("K$i",$extension['state']);
                if($extension['state']=='stable'){
                    $excel->getActiveSheet()->getStyle("K$i")->applyFromArray($syleYes);
                }elseif ($extension['state']=='beta') {
                    $excel->getActiveSheet()->getStyle("K$i")->applyFromArray($syleBeta);
                }elseif ($extension['state']=='alpha') {
                    $excel->getActiveSheet()->getStyle("K$i")->applyFromArray($syleNo);
                }
                if($extension['terObject']!=null){
                    $row->setCellValue("L$i",$this->translate('ter'));
                }else{
                   $row->setCellValue("L$i",$this->translate('custom'));
                }
                $k++;
                $i++;
            }
        }
        //Add Data in Excel From array $extensionlist end
        //Set OverviewReport Start
        $row->setCellValue("A$i",$this->translate('overviewReport'));
        $row->setCellValue("D$i",$overviewReport['totalInstalled']);
        $row->setCellValue("E$i",$overviewReport['totalCompatible6']);
        $row->setCellValue("F$i",$overviewReport['totalCompatible7']);
        $row->setCellValue("G$i",$overviewReport['totalCompatible8']);
        $row->setCellValue("H$i",$overviewReport['totalCompatible9']);
        $excel->getActiveSheet()->getRowDimension("$i")->setRowHeight(30);
        $excel->setActiveSheetIndex(0)->mergeCells("A$i:C$i");

        $excel->getActiveSheet()->getStyle("B11:B$i")->applyFromArray($titleStyle);
        $excel->getActiveSheet()->getStyle("C11:C$i")->applyFromArray($titleStyle);
        $excel->getActiveSheet()->getStyle("A$i:M$i")->applyFromArray($overviewReportStyle);
       
        //Set OverviewReport End

         //Set System Requirement Start
        $j=$i+2;
        $k=$i+3;
        $l=$i+4;
        $excel->setActiveSheetIndex(0)->mergeCells("B$j:D$j");
        $excel->getActiveSheet()->getStyle("B$j")->applyFromArray($boldOnly);
        $excel->getActiveSheet()->getStyle("B$k:D$k")->applyFromArray($extTableHeadingStype);
        $row->setCellValue("B$j",$this->translate('targetSystemRequirement').''.$sysDetail['targetVersion']);

        $row->setCellValue("B$k",$this->translate('targetSystemRequirement.module'));
        $row->setCellValue("C$k",$this->translate('targetSystemRequirement.current'));
        $row->setCellValue("D$k",$this->translate('targetSystemRequirement.required'));
        
        foreach ($targetSystemRequirement as $key => $module) {
            $row->setCellValue("B$l",$this->translate('targetSystemRequirement.'.$key));
            $row->setCellValue("C$l",$module['current']);
            $row->setCellValue("D$l",$module['required']);
            $l++;
        }
        $excel->getActiveSheet()->getStyle("B$k:B$l")->applyFromArray($titleStyle);
         //Set System Requirement End

        //Add Data in Excel From array $data End
        $excel->getActiveSheet()->setTitle($this->translate('sheet.title'));
        $excel->setActiveSheetIndex(0);
        ob_start();
        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        
        $objWriter->save("php://output");
        $xlsData = ob_get_contents();
        ob_end_clean();
        echo $xlsData;die;
    }

    /**
     * This method is used for  get detail list of local extension
    */
    public function getAllExtensions($myTargetVersion)
    { 
        $i=1;
        $totalCompatible6=0;
        $totalCompatible7=0;
        $totalCompatible8=0;
        $totalInstalled=0;
        $totalNonInstalled=0;
        $assignArray = array();
        $extensionlist = array();
        $overviewReport = array();
        

        //Get extension list
        $myExtList = $this->objectManager->get(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        foreach ($allExtensions as $extensionKey => $nsExt) {
            //Filter all local extension for whole TER data start
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key']!='ns_ext_compatibility') {
                $newNsVersion=0;
                $extArray = $this->extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);  
                //Fetch typo3 depency of extesion  start
                if(count($extArray)!=0){
                    foreach ($extArray as $extension) {
                        foreach ($extension->getDependencies() as $dependency) {
                            if ($dependency->getIdentifier() === 'typo3') {
                                // Extract min TYPO3 CMS version (lowest)
                                $minVersion= $dependency->getLowestVersion();
                                // Extract max TYPO3 CMS version (higherst)
                                $maxVersion=$dependency->getHighestVersion();
                                
                                if($minVersion<=7 &&  $maxVersion>=6){
                                    $nsExt['compatible6']=1;
                                }
                                if($minVersion<=8 &&  $maxVersion>=7){
                                    $nsExt['compatible7']=1;
                                }
                                if($minVersion<=9 &&  $maxVersion>=8){
                                    $nsExt['compatible8']=1;
                                }
                                if($minVersion<=10 &&  $maxVersion>=9){
                                    $nsExt['compatible9']=1;
                                }
                                if((($maxVersion>(int)$myTargetVersion && $maxVersion<=(int)$myTargetVersion+1)|| $minVersion>(int)$myTargetVersion && $minVersion<=(int)$myTargetVersion+1) && ($newNsVersion<$extension->getVersion())){
                                    $newNsVersion=$extension->getVersion();
                                    $nsExt['newVersion']=$newNsVersion;
                                }
                            }
                        }
                    }
                }
                else{
                    //Check dependancy for custom extension start
                    foreach ($nsExt['constraints']['depends'] as $type => $customDepency) {
                        if($type=='typo3'){
                             $version=explode('-',$customDepency);
                            if(($version[0]<6 && $version[1]>=4) || ($version[0]<6 && $version[1]==0.0)){
                                $nsExt['compatible4']=1;
                            }
                            if($version[0]<=7 && $version[1]>=6){
                                $nsExt['compatible6']=1;
                            }
                            if($version[0]<=8 && $version[1]>=7){
                                $nsExt['compatible7']=1;
                            }
                            if($version[0]<=9 && $version[1]>=8){
                                $nsExt['compatible8']=1;
                            }
                            if($version[0]<=10 && $version[1]>=9){
                                $nsExt['compatible9']=1;
                            }
                        }
                    }
                    //Check dependancy for custom extension end
                }
                //Fetch typo3 depency of extesion  end

                // Set overview Report start
              
                
                if($extArray[0] && empty($nsExt['newVersion'])){
                    $nsExt['newVersion']=$extArray[0]->getVersion();
                }
            
                //Count Total compatibility Start
                    if($nsExt['compatible4']==1){
                        $totalCompatible4++;
                    }
                    if($nsExt['compatible6']==1){
                        $totalCompatible6++;
                    }
                    if($nsExt['compatible7']==1){
                        $totalCompatible7++;
                    }
                    if($nsExt['compatible8']==1){
                        $totalCompatible8++;
                    }
                    if($nsExt['compatible9']==1){
                        $totalCompatible9++;
                    }
                    if($nsExt['installed']==1){
                       $totalInstalled++;
                    }else{
                       $totalNonInstalled++;
                    }
                //Count Total compatibility End


                if($nsExt['installed']==1){
                   $totalInstalled++;
                }else{
                   $totalNonInstalled++;
                }
                // Set overview Report end

                $extensionlist[$i] = $nsExt;
                $i++;
            }
            //Filter all local extension for whole TER data end
  
        }
        //Set overview array start
        $overviewReport['totalInstalled']=$totalInstalled;
        $overviewReport['totalNonInstalled']=$totalNonInstalled;
        $overviewReport['totalCompatible6']=$totalCompatible6;
        $overviewReport['totalCompatible7']=$totalCompatible7;
        $overviewReport['totalCompatible8']=$totalCompatible8;
        $overviewReport['totalCompatible9']=$totalCompatible9;
        //Set overview array end
       
        $assignArray['overviewReport']=$overviewReport;
        $assignArray['extensionlist']=$extensionlist;

        return $assignArray;
    }

    /**
     * This method is used of get sysimformation
    */
    public function getSysDetail()
    {   
        $sysDetail = array();
        $extConfig= unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_ext_compatibility']);
        $sysDetail['phpversion']=substr(phpversion(), 0, 6);;
        $sysDetail['targetVersion']=$extConfig['typo3TargetVersion'];
        $sysDetail['sitename']=$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $sysDetail['typo3version']=VersionNumberUtility::getNumericTypo3Version();
        $sysDetail['totalPages']= $this->NsExtCompatibilityRepository->countPages();
        $sysDetail['totalDomain']= $this->NsExtCompatibilityRepository->countDomain();
        $sysDetail['totalLang']= $this->NsExtCompatibilityRepository->sysLang();

        return $sysDetail;
    }


   /**
    * This method is used for get System requirement for target typo3 version
    **/
    public function getSysRequirementForTargetVersion ($targetVersion) {
        exec('convert -version',$imgmagic);
        preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', shell_exec( 'mysql -V'), $mysqlVersion);

        $typo3Config=array(
            '4.x' => array(
                'php' =>array(
                    'required'=>'5.2-5.5',
                    'current'=>substr(phpversion(), 0, 6)
                ) ,
                'mysql' => array(
                    'required'=>'5.0-5.5',
                    'current'=>$mysqlVersion[0],
                ),
                'imageMagick' => array(
                    'required'=>'-',
                    'current'=>substr($imgmagic[0], 21, 5)
                ),
                'maxExecutionTime' => array(
                    'required'=>'240',
                    'current'=>ini_get(max_execution_time)
                ),

                'memoryLimit' => array(
                    'required'=>'128M',
                    'current'=>ini_get(memory_limit)
                ),
                'maxInputVars' => array(
                    'required'=>'1500',
                    'current'=>ini_get(max_input_vars) 
                ),
                'uploadMaxSize' => array(
                    'required'=>'200M',
                    'current'=>ini_get(upload_max_filesize)
                ),
                'postMaxSize' =>array( 
                    'required'=>'800M',
                    'current'=>ini_get(post_max_size)
                )
            ),
            '6.x' => array(
                'php' =>array(
                    'required'=>'5.3',
                    'current'=>substr(phpversion(), 0, 6)
                ) ,
                'mysql' => array(
                    'required'=>'5.1-5.6',
                    'current'=>$mysqlVersion[0],
                ),
                'imageMagick' => array(
                    'required'=>'-',
                    'current'=>substr($imgmagic[0], 21, 5)
                ),
                'maxExecutionTime' => array(
                    'required'=>'240',
                    'current'=>ini_get(max_execution_time)
                ),

                'memoryLimit' => array(
                    'required'=>'128M',
                    'current'=>ini_get(memory_limit)
                ),
                'maxInputVars' => array(
                    'required'=>'1500',
                    'current'=>ini_get(max_input_vars) 
                ),
                'uploadMaxSize' => array(
                    'required'=>'200M',
                    'current'=>ini_get(upload_max_filesize)
                ),
                'postMaxSize' =>array( 
                    'required'=>'800M',
                    'current'=>ini_get(post_max_size)
                )
            ),
            '7.x' => array(
                'php' =>array(
                    'required'=>'5.5',
                    'current'=>substr(phpversion(), 0, 6)
                ) ,
                'mysql' => array(
                    'required'=>'5.5-5.7',
                    'current'=>$mysqlVersion[0],
                ),
                'imageMagick' => array(
                    'required'=>'6',
                    'current'=>substr($imgmagic[0], 21, 5)
                ),
                'maxExecutionTime' => array(
                    'required'=>'240',
                    'current'=>ini_get(max_execution_time)
                ),

                'memoryLimit' => array(
                    'required'=>'128M',
                    'current'=>ini_get(memory_limit)
                ),
                'maxInputVars' => array(
                    'required'=>'1500',
                    'current'=>ini_get(max_input_vars) 
                ),
                'uploadMaxSize' => array(
                    'required'=>'200M',
                    'current'=>ini_get(upload_max_filesize)
                ),
                'postMaxSize' =>array( 
                    'required'=>'800M',
                    'current'=>ini_get(post_max_size)
                )
            ),
            '8.x' => array(
                'php' =>array(
                    'required'=>'7',
                    'current'=>substr(phpversion(), 0, 6)
                ) ,
                'mysql' => array(
                    'required'=>'5.0-5.7',
                    'current'=>$mysqlVersion[0],
                ),
                'imageMagick' => array(
                    'required'=>'6',
                    'current'=>substr($imgmagic[0], 21, 5)
                ),
                'maxExecutionTime' => array(
                    'required'=>'240',
                    'current'=>ini_get(max_execution_time)
                ),

                'memoryLimit' => array(
                    'required'=>'128M',
                    'current'=>ini_get(memory_limit)
                ),
                'maxInputVars' => array(
                    'required'=>'1500',
                    'current'=>ini_get(max_input_vars) 
                ),
                'uploadMaxSize' => array(
                    'required'=>'200M',
                    'current'=>ini_get(upload_max_filesize)
                ),
                'postMaxSize' =>array( 
                    'required'=>'800M',
                    'current'=>ini_get(post_max_size)
                )
            ),
            '9.x' => array(
                'php' =>array(
                    'required'=>'7.2',
                    'current'=>substr(phpversion(), 0, 6)
                ) ,
                'mysql' => array(
                    'required'=>'5.0-5.7',
                    'current'=>$mysqlVersion[0],
                ),
                'imageMagick' => array(
                    'required'=>'6',
                    'current'=>substr($imgmagic[0], 21, 5)
                ),
                'maxExecutionTime' => array(
                    'required'=>'240',
                    'current'=>ini_get(max_execution_time)
                ),

                'memoryLimit' => array(
                    'required'=>'128M',
                    'current'=>ini_get(memory_limit)
                ),
                'maxInputVars' => array(
                    'required'=>'1500',
                    'current'=>ini_get(max_input_vars) 
                ),
                'uploadMaxSize' => array(
                    'required'=>'200M',
                    'current'=>ini_get(upload_max_filesize)
                ),
                'postMaxSize' =>array( 
                    'required'=>'800M',
                    'current'=>ini_get(post_max_size)
                )
            ),
        );
       return $typo3Config[$targetVersion];
    }

    
    /**
     * @param string $key
     * @return null|string
     */
    protected function translate($key,$arguments=''){
        if($arguments!=''){
            return Localize::translate($key, 'ns_ext_compatibility',$arguments);
        }else{
            return Localize::translate($key, 'ns_ext_compatibility');
        }

    }


}
