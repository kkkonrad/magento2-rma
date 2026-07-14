<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Resolution;

use Kkkonrad\Rma\Model\RmaResolutionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution as ResolutionResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::resolutions_manage';

    public function __construct(
        Context $context,
        private readonly RmaResolutionFactory $resolutionFactory,
        private readonly ResolutionResource $resolutionResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('resolution_id');

        if ($id) {
            try {
                $model = $this->resolutionFactory->create();
                $this->resolutionResource->load($model, $id);

                if ($model->getResolutionId()) {
                    $this->resolutionResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the return resolution.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This return resolution no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['resolution_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a return resolution to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
