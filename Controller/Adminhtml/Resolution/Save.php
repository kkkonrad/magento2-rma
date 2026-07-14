<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Resolution;

use Kkkonrad\Rma\Model\RmaResolutionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution as ResolutionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::resolutions_manage';

    public function __construct(
        Context $context,
        private readonly RmaResolutionFactory $resolutionFactory,
        private readonly ResolutionResource $resolutionResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('resolution_id');
            $model = $this->resolutionFactory->create();

            if ($id) {
                $this->resolutionResource->load($model, $id);
                if (!$model->getResolutionId()) {
                    $this->messageManager->addErrorMessage(__('This return resolution no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            }

            $model->setData($data);

            try {
                $this->resolutionResource->save($model);
                $this->dataPersistor->clear('kkkonrad_rma_resolution');
                $this->messageManager->addSuccessMessage(__('You saved the return resolution.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['resolution_id' => $model->getResolutionId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the return resolution.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_resolution', $data);
            return $resultRedirect->setPath('*/*/edit', ['resolution_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
