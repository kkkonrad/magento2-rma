<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\RmaFileDownloader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Download extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaFileDownloader $fileDownloader
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        $rmaId = (int) $this->getRequest()->getParam('rma_id');
        try {
            $rma = $this->rmaRepository->getById($rmaId);
            $attachmentId = (int) $this->getRequest()->getParam('attachment_id');
            return $this->fileDownloader->download($rma, $attachmentId > 0 ? $attachmentId : null);
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(__('The requested file is unavailable.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['rma_id' => $rmaId]);
        }
    }
}
