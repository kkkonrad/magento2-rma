<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
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
        $rmaId = (int) $this->getRequest()->getParam('rma_id');

        try {
            $this->rmaRepository->deleteById($rmaId);
            $this->messageManager->addSuccessMessage(__('RMA has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/index');
    }
}
