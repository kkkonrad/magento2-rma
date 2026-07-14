<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Reason;

use Kkkonrad\Rma\Model\RmaReasonFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as ReasonResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::reasons_manage';

    public function __construct(
        Context $context,
        private readonly RmaReasonFactory $reasonFactory,
        private readonly ReasonResource $reasonResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('reason_id');
            $model = $this->reasonFactory->create();

            if ($id) {
                $this->reasonResource->load($model, $id);
                if (!$model->getReasonId()) {
                    $this->messageManager->addErrorMessage(__('This return reason no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            }

            $model->setData($data);

            try {
                $this->reasonResource->save($model);
                $this->dataPersistor->clear('kkkonrad_rma_reason');
                $this->messageManager->addSuccessMessage(__('You saved the return reason.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['reason_id' => $model->getReasonId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the return reason.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_reason', $data);
            return $resultRedirect->setPath('*/*/edit', ['reason_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
