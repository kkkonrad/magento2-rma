<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Reject extends Action
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
        $rmaId  = (int) $this->getRequest()->getParam('rma_id');
        $comment = (string) $this->getRequest()->getParam('comment', '');

        try {
            $this->rmaManagement->reject($rmaId, $comment ?: null);
            $this->messageManager->addSuccessMessage(__('RMA has been rejected.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while rejecting the RMA.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
