<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Reason;

use Kkkonrad\Rma\Model\RmaReasonFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as ReasonResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::reasons_manage';

    public function __construct(
        Context $context,
        private readonly RmaReasonFactory $reasonFactory,
        private readonly ReasonResource $reasonResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('reason_id');

        if ($id) {
            try {
                $model = $this->reasonFactory->create();
                $this->reasonResource->load($model, $id);
                
                if ($model->getReasonId()) {
                    $this->reasonResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the return reason.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This return reason no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['reason_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a return reason to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
