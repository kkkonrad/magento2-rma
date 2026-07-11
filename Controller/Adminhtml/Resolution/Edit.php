<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Resolution;

use Kkkonrad\Rma\Model\RmaResolutionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution as ResolutionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::resolutions_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaResolutionFactory $resolutionFactory,
        private readonly ResolutionResource $resolutionResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('resolution_id');
        $model = $this->resolutionFactory->create();

        if ($id) {
            $this->resolutionResource->load($model, $id);
            if (!$model->getResolutionId()) {
                $this->messageManager->addErrorMessage(__('This return resolution no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_resolution', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getResolutionId() ? __('Edit Return Resolution "%1"', $model->getLabel()) : __('New Return Resolution')
        );

        return $resultPage;
    }
}
