<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Reason;

use Kkkonrad\Rma\Model\RmaReasonFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as ReasonResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::reasons_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaReasonFactory $reasonFactory,
        private readonly ReasonResource $reasonResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('reason_id');
        $model = $this->reasonFactory->create();

        if ($id) {
            $this->reasonResource->load($model, $id);
            if (!$model->getReasonId()) {
                $this->messageManager->addErrorMessage(__('This return reason no longer exists.'));
                return $this->_redirect('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_reason', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getReasonId() ? __('Edit Return Reason "%1"', $model->getLabel()) : __('New Return Reason')
        );

        return $resultPage;
    }
}
