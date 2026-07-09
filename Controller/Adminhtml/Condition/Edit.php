<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Condition;

use Kkkonrad\Rma\Model\RmaConditionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as ConditionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::conditions_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaConditionFactory $conditionFactory,
        private readonly ConditionResource $conditionResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('condition_id');
        $model = $this->conditionFactory->create();

        if ($id) {
            $this->conditionResource->load($model, $id);
            if (!$model->getConditionId()) {
                $this->messageManager->addErrorMessage(__('This item condition no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_condition', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getConditionId() ? __('Edit Item Condition "%1"', $model->getLabel()) : __('New Item Condition')
        );

        return $resultPage;
    }
}
