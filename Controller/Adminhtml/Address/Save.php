<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Address;

use Kkkonrad\Rma\Model\RmaAddressFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as AddressResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::addresses_manage';

    public function __construct(
        Context $context,
        private readonly RmaAddressFactory $addressFactory,
        private readonly AddressResource $addressResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('address_id');
            $model = $this->addressFactory->create();

            if ($id) {
                $this->addressResource->load($model, $id);
                if (!$model->getAddressId()) {
                    $this->messageManager->addErrorMessage(__('This return address no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            } else {
                unset($data['address_id']);
            }

            $model->setData($data);

            try {
                $this->addressResource->save($model);

                // If this is set as default, clear default status for all other addresses
                if ($model->getIsDefault()) {
                    $connection = $this->addressResource->getConnection();
                    $connection->update(
                        $this->addressResource->getMainTable(),
                        ['is_default' => 0],
                        ['address_id != ?' => (int)$model->getAddressId()]
                    );
                }

                $this->dataPersistor->clear('kkkonrad_rma_address');
                $this->messageManager->addSuccessMessage(__('You saved the return address.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['address_id' => $model->getAddressId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the return address.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_address', $data);
            return $resultRedirect->setPath('*/*/edit', ['address_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
