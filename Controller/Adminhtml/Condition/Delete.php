<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Condition;

use Kkkonrad\Rma\Model\RmaConditionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as ConditionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::conditions_manage';

    public function __construct(
        Context $context,
        private readonly RmaConditionFactory $conditionFactory,
        private readonly ConditionResource $conditionResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('condition_id');

        if ($id) {
            try {
                $model = $this->conditionFactory->create();
                $this->conditionResource->load($model, $id);
                
                if ($model->getConditionId()) {
                    $this->conditionResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the item condition.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This item condition no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['condition_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find an item condition to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
