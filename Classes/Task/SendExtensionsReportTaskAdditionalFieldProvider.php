<?php

namespace NITSAN\NsExtCompatibility\Task;

/**
 * Class SendExtensionsReportTaskAdditionalFieldProvider
 */

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localize;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use \TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * SendExtensionsReportTaskAdditionalFieldProvider
 * @extensionScannerIgnoreLine
 */

// @extensionScannerIgnoreFile
class SendExtensionsReportTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Create additional fields
     * @param array $taskInfo
     * @param SendExtensionsReportTask $task
     * @param SchedulerModuleController $parentObject
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject): array
    {
        if (empty($taskInfo['mailTo'])) {
            if (($parentObject->CMD ?? '') == 'add') {
                $taskInfo['mailTo'] = '';
            } else {
                $taskInfo['mailTo'] = $task->mailTo ?? '';
            }
        }

        if (empty($taskInfo['mailSender'])) {
            if (($parentObject->CMD ?? '') == 'add') {
                $taskInfo['mailSender'] = '';
            } else {
                $taskInfo['mailSender'] = $task->mailSender ?? '';
            }
        }

        if (empty($taskInfo['excludeExtensionsFromCheck'])) {
            if (($parentObject->CMD ?? '') == 'add') {
                $taskInfo['excludeExtensionsFromCheck'] = '';
            } else {
                $taskInfo['excludeExtensionsFromCheck'] = $task->excludeExtensionsFromCheck ?? '';
            }
        }

        // Inputfields
        $additionalFields = [
            'task_mailSender' => [
                'code' => '<input type="text" name="tx_scheduler[mailSender]" value="' . $taskInfo['mailSender'] . '" />',
                'label' => $this->translate('task.sendExtensionsReportTask.additionalFields.mailSender.label')
            ],
            'task_mailTo' => [
                'code' => '<input type="text" name="tx_scheduler[mailTo]" value="' . $taskInfo['mailTo'] . '"/>',
                'label' => $this->translate('task.sendExtensionsReportTask.additionalFields.mailTo.label')
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
     * @param SchedulerModuleController $parentObject
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject): bool
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
        $mailTos = explode(',', $submittedData['mailTo']);
        foreach ($mailTos as $mailTo) {
            if (empty($mailTo) || filter_var($mailTo, FILTER_VALIDATE_EMAIL) === false) {
                $ok = false;
                $errorMsgs[] = $this->translate('task.sendExtensionsReportTask.additionalFields.emailMail.validation') . ': ' . $mailTo;
            }
        }

        if ($ok) {
            return true;
        }

        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, implode(' / ', $errorMsgs), '', ContextualFeedbackSeverity::ERROR);
        $service = GeneralUtility::makeInstance(FlashMessageService::class);
        $queue = $service->getMessageQueueByIdentifier();
        $queue->enqueue($flashMessage);

        return false;
    }

    /**
     * Saves the input value
     * @param array $submittedData
     * @param AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $task->mailSender = trim($submittedData['mailSender']);
        $task->mailTo = trim($submittedData['mailTo']);
        $task->excludeExtensionsFromCheck = trim($submittedData['excludeExtensionsFromCheck']);
    }

    /**
     * @param $key
     * @param string $arguments
     * @return null|string
     */
    protected function translate($key, string $arguments = ''): ?string
    {
        if ($arguments != '') {
            return Localize::translate($key, 'ns_ext_compatibility', $arguments);
        } else {
            return Localize::translate($key, 'ns_ext_compatibility');
        }
    }
}
