<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Address;

use Kkkonrad\Rma\Model\RmaAddressFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as AddressResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::addresses_manage';

    public function __construct(
        Context $context,
        private readonly RmaAddressFactory $addressFactory,
        private readonly AddressResource $addressResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('address_id');

        if ($id) {
            try {
                $model = $this->addressFactory->create();
                $this->addressResource->load($model, $id);

                if ($model->getAddressId()) {
                    $this->addressResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the return address.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This return address no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['address_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a return address to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
