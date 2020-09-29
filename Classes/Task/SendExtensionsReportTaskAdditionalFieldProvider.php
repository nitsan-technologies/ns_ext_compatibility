<?php
namespace NITSAN\NsExtCompatibility\Task;

/**
 * Class SendExtensionsReportTaskAdditionalFieldProvider
 */
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;

class SendExtensionsReportTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{

    /**
     * Create additional fields
     * @param array $taskInfo
     * @param \NITSAN\NsExtCompatibility\Task\SendExtensionsReportTask $task
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        if (empty($taskInfo['mailTo'])) {
            if ($parentObject->CMD=='add') {
                $taskInfo['mailTo']='';
            } else {
                $taskInfo['mailTo']=$task->mailTo;
            }
        }

        if (empty($taskInfo['mailSender'])) {
            if ($parentObject->CMD=='add') {
                $taskInfo['mailSender']='';
            } else {
                $taskInfo['mailSender']=$task->mailSender;
            }
        }

        if (empty($taskInfo['excludeExtensionsFromCheck'])) {
            if ($parentObject->CMD=='add') {
                $taskInfo['excludeExtensionsFromCheck']='';
            } else {
                $taskInfo['excludeExtensionsFromCheck']=$task->excludeExtensionsFromCheck;
            }
        }

        // Inputfields
        $additionalFields = [
            'task_mailSender' => [
                'code' => '<input type="text" name="tx_scheduler[mailSender]" value="' . $taskInfo['mailSender'] . '" />',
                'label' => $this->translate('task.sendExtensionsReportTask.additionalFields.mailSender.label')
            ],
            'task_mailTo'=>[
                'code'=>'<input type="text" name="tx_scheduler[mailTo]" value="' . $taskInfo['mailTo'] . '"/>',
                'label'=>$this->translate('task.sendExtensionsReportTask.additionalFields.mailTo.label')
            ],
            'task_excludeExtensionsFromCheck' => [
                'code' => '<textarea name="tx_scheduler[excludeExtensionsFromCheck]">' . $taskInfo['excludeExtensionsFromCheck'] . '</textarea>',
                'label' => $this->translate('task.sendExtensionsReportTask.additionalFields.excludeExtensionsFromCheck.label')
            ],
        ];

        return $additionalFields;
    }

    /**
     * Validates the input value(s)
     * @param array $submittedData
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $ok = true;
        $errorMsgs = [];

        $submittedData['mailSender'] = trim($submittedData['mailSender']);
        $submittedData['mailTo'] = trim($submittedData['mailTo']);

        // Validate email from
        if (empty($submittedData['mailSender']) || filter_var($submittedData['mailSender'], FILTER_VALIDATE_EMAIL) === false) {
            $ok = false;
            $errorMsgs[] = $this->translate('task.sendExtensionsReportTask.additionalFields.emailMail.validation') . ': ' . $submittedData['mailSender'];
        }
        // Validate email to addresses
        $mailTos =explode(',', $submittedData['mailTo']);
        foreach ($mailTos as $mailTo) {
            if (empty($mailTo) || filter_var($mailTo, FILTER_VALIDATE_EMAIL) === false) {
                $ok = false;
                $errorMsgs[] = $this->translate('task.sendExtensionsReportTask.additionalFields.emailMail.validation') . ': ' . $mailTo;
            }
        }

        if ($ok) {
            return true;
        }

        $parentObject->addMessage(implode(' / ', $errorMsgs), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        return false;
    }

    /**
     * Saves the input value
     * @param array $submittedData
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $task->mailSender = trim($submittedData['mailSender']);
        $task->mailTo = trim($submittedData['mailTo']);
        $task->excludeExtensionsFromCheck = trim($submittedData['excludeExtensionsFromCheck']);
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
