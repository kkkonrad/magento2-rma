<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class AddMessage extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly RmaManagementInterface $rmaManagement
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $rmaId      = (int) $this->getRequest()->getParam('rma_id');
        $message    = trim((string) $this->getRequest()->getParam('message'));
        $isInternal = (bool) $this->getRequest()->getParam('is_internal', false);

        try {
            if (!$rmaId || !$message) {
                throw new LocalizedException(__('Message cannot be empty.'));
            }

            $adminUser = $this->_auth->getUser();
            $authorName = $adminUser ? $adminUser->getName() : 'Admin';
            $authorId = $adminUser ? (int)$adminUser->getId() : null;

            $this->rmaManagement->addMessage(
                $rmaId,
                $message,
                RmaMessageInterface::AUTHOR_ADMIN,
                $authorId,
                $authorName,
                $isInternal
            );

            $this->messageManager->addSuccessMessage(__('Message has been added.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while adding the message.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
