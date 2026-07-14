<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class ChangeStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_change_status';

    public function __construct(
        Context $context,
        private readonly RmaManagementInterface $rmaManagement
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rmaId     = (int) $this->getRequest()->getParam('rma_id');
        $newStatus = (string) $this->getRequest()->getParam('status');
        $comment   = $this->getRequest()->getParam('comment');

        if (!$rmaId || !$newStatus) {
            $this->messageManager->addErrorMessage(__('Invalid parameters.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $adminId = (int) $this->_auth->getUser()->getId();
            $this->rmaManagement->changeStatus($rmaId, $newStatus, $comment, 'admin', $adminId);
            $this->messageManager->addSuccessMessage(__('RMA status has been updated.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while updating the RMA status.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
