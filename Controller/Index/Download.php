<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\RmaFileDownloader;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Download implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Session $customerSession,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaFileDownloader $fileDownloader,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                throw new \Magento\Framework\Exception\AuthorizationException(__('Please log in.'));
            }
            $rma = $this->rmaRepository->getByIdForCustomer(
                (int) $this->request->getParam('rma_id'),
                (int) $this->customerSession->getCustomerId()
            );
            $attachmentId = (int) $this->request->getParam('attachment_id');
            return $this->fileDownloader->download($rma, $attachmentId > 0 ? $attachmentId : null);
        } catch (\Throwable) {
            return $this->redirectFactory->create()->setPath('rma/index/index');
        }
    }
}
