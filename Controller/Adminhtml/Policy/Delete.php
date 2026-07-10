<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Policy;

use Kkkonrad\Rma\Model\RmaPolicyFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy as PolicyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::policies_manage';

    public function __construct(
        Context $context,
        private readonly RmaPolicyFactory $policyFactory,
        private readonly PolicyResource $policyResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('policy_id');

        if ($id) {
            try {
                $model = $this->policyFactory->create();
                $this->policyResource->load($model, $id);

                if ($model->getPolicyId()) {
                    $this->policyResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the return policy.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This return policy no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['policy_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a return policy to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
