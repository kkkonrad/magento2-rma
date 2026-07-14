<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Approve extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_approve';

    public function __construct(
        Context $context,
        private readonly RmaManagementInterface $rmaManagement
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $rmaId   = (int) $this->getRequest()->getParam('rma_id');
        $comment = $this->getRequest()->getParam('comment');

        try {
            $this->rmaManagement->approve($rmaId, $comment);
            $this->messageManager->addSuccessMessage(__('RMA has been approved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while approving the RMA.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
