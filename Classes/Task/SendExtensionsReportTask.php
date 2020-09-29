<?php
namespace NITSAN\NsExtCompatibility\Task;

/**
 * Class SendExtensionsReportTask
 */
use NITSAN\NsExtCompatibility\Utility\Extension;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class SendExtensionsReportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var string
     */
    public $mailFrom;

    /**
     * @var string
     */
    public $mailTo;

    /**
     * @var string
     */
    public $excludeExtensionsFromCheck;

    /**
     * Executs the scheduler job
     * @return bool
     */
    public function execute()
    {
        $this->getExtReoport();
        return true;
    }

    /**
     * Checks, if there are updates available fo rinstalled extensions
     */
    protected function getExtReoport()
    {
        $extReports=[];
        $i=1;
        // Create objects
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $extensionRepository = $objectManager->get(ExtensionRepository::class);

        $myExtList = $objectManager->get(ListUtility::class);
        $allExtensions = $myExtList->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $excludeExtensions =  GeneralUtility::trimExplode(',', $this->excludeExtensionsFromCheck);

        foreach ($allExtensions as $extensionKey => $nsExt) {
            if (strtolower($nsExt['type']) == 'local' && $nsExt['key']!='ns_ext_compatibility' && !in_array($extensionKey, $excludeExtensions) && $nsExt['updateAvailable']==true && $nsExt['installed']==true) {
                $extArray = $extensionRepository->findByExtensionKeyOrderedByVersion($nsExt['key']);

                if ($extArray[0]) {
                    $nsExt['newVersion']=$extArray[0]->getVersion();
                    $nsExt['newState']=$extArray[0]->getState();
                }
                $extReports[$i]=$nsExt;
                $i++;
            }
        }

        $subject =$this->translate('task.checkExtensionsTask.maiSubject', ['sitename'=>$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']]);
        $receiver =  GeneralUtility::trimExplode(',', $this->mailTo);
        $sender= $this->mailSender;
        $body = $this->renderMailContent($extReports);
        return $this->sendMail($receiver, $sender, $subject, $body);
    }

    /**
     * renders a fluid mail template
     *
     * @param array $extReports
     *
     * @return string
    */
    protected function renderMailContent($extReports)
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $domain= explode('/typo3/', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        if (((isset($_SERVER['HTTPS'])) && (strtolower($_SERVER['HTTPS']) == 'on')) || ((isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'))) {
            $beDomain='https://' . $domain[0];
        } else {
            $beDomain='http://' . $domain[0] . '/typo3/';
        }

        $projectName=$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];

        $view->getRequest()->setControllerExtensionName('ns_ext_compatibility'); // path the extension name to get translation work
        $view->setPartialRootPaths(['0'=>ExtensionManagementUtility::extPath('ns_ext_compatibility') . 'Resources/Private/Partials/']);
        $view->setLayoutRootPaths(['0'=>ExtensionManagementUtility::extPath('ns_ext_compatibility') . 'Resources/Private/Layouts/']);
        $view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('ns_ext_compatibility') . 'Resources/Private/Templates/Mail/Report.html');
        $view->assign('extReports', $extReports);
        $view->assign('projectName', $projectName);
        $view->assign('beDomain', $beDomain);

        return $view->render();
    }

    /**
     * sends an email
     *
     * @param array $receiver Array with receiver
     * @param array $sender Array with sender
     * @param string $subject Subject of mail
     * @param string $body Body content for mail
     * @param string $attachment Path to a file
     * @param string $bodyType text/html or text/plain
     *
     * return boolean
     */
    protected function sendMail($receiver = [], $sender = [], $subject = '', $body = '', $bodyType = 'text/html')
    {
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        if (!empty($receiver) && !empty($sender)) {
            return $mail->setFrom($sender)
                            ->setTo($receiver)
                            ->setSubject($subject)
                            ->setBody($body, $bodyType)
                            ->send();
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @param array $arguments
     * @return null|string
    */
    protected function translate($key, $arguments='')
    {
        if ($arguments!='') {
            return Localize::translate($key, 'ns_ext_compatibility', $arguments);
        } else {
            return Localize::translate($key, 'ns_ext_compatibility');
        }
    }
}
