<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Address;

use Kkkonrad\Rma\Model\RmaAddressFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as AddressResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::addresses_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaAddressFactory $addressFactory,
        private readonly AddressResource $addressResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('address_id');
        $model = $this->addressFactory->create();

        if ($id) {
            $this->addressResource->load($model, $id);
            if (!$model->getAddressId()) {
                $this->messageManager->addErrorMessage(__('This return address no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_address', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getAddressId() ? __('Edit Return Address "%1"', $model->getName()) : __('New Return Address')
        );

        return $resultPage;
    }
}
