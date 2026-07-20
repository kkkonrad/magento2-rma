<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Policy;

use Kkkonrad\Rma\Model\RmaPolicyFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy as PolicyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::policies_manage';

    public function __construct(
        Context $context,
        private readonly RmaPolicyFactory $policyFactory,
        private readonly PolicyResource $policyResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('policy_id');
            $model = $this->policyFactory->create();

            if ($id) {
                $this->policyResource->load($model, $id);
                if (!$model->getPolicyId()) {
                    $this->messageManager->addErrorMessage(__('This return policy no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            } else {
                unset($data['policy_id']);
            }

            $model->setData($data);

            try {
                $this->policyResource->save($model);
                $this->dataPersistor->clear('kkkonrad_rma_policy');
                $this->messageManager->addSuccessMessage(__('You saved the return policy.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['policy_id' => $model->getPolicyId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the return policy.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_policy', $data);
            return $resultRedirect->setPath('*/*/edit', ['policy_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
