<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Policy;

use Kkkonrad\Rma\Model\RmaPolicyFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy as PolicyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::policies_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaPolicyFactory $policyFactory,
        private readonly PolicyResource $policyResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('policy_id');
        $model = $this->policyFactory->create();

        if ($id) {
            $this->policyResource->load($model, $id);
            if (!$model->getPolicyId()) {
                $this->messageManager->addErrorMessage(__('This return policy no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_policy', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getPolicyId() ? __('Edit Return Policy "%1"', $model->getName()) : __('New Return Policy')
        );

        return $resultPage;
    }
}
