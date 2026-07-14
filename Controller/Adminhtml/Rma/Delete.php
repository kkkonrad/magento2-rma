<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_delete';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rmaId = (int) $this->getRequest()->getParam('rma_id');

        if (!$rmaId) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->rmaRepository->deleteById($rmaId);
            $this->messageManager->addSuccessMessage(__('RMA has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the RMA.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
