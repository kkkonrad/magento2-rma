<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Condition;

use Kkkonrad\Rma\Model\RmaConditionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as ConditionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::conditions_manage';

    public function __construct(
        Context $context,
        private readonly RmaConditionFactory $conditionFactory,
        private readonly ConditionResource $conditionResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('condition_id');
            $model = $this->conditionFactory->create();

            if ($id) {
                $this->conditionResource->load($model, $id);
                if (!$model->getConditionId()) {
                    $this->messageManager->addErrorMessage(__('This item condition no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            }

            $model->setData($data);

            try {
                $this->conditionResource->save($model);
                $this->dataPersistor->clear('kkkonrad_rma_condition');
                $this->messageManager->addSuccessMessage(__('You saved the item condition.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['condition_id' => $model->getConditionId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the item condition.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_condition', $data);
            return $resultRedirect->setPath('*/*/edit', ['condition_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
