<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Kkkonrad\Rma\Model\RmaFileDownloader;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Download implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly GuestAccessToken $guestAccessToken,
        private readonly RmaFileDownloader $fileDownloader,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        try {
            $rma = $this->rmaRepository->getById((int) $this->request->getParam('rma_id'));
            if ((int) $rma->getCustomerId() !== 0
                || !$this->guestAccessToken->isValid($rma, (string) $this->request->getParam('hash'))
            ) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Access denied.'));
            }
            $attachmentId = (int) $this->request->getParam('attachment_id');
            return $this->fileDownloader->download($rma, $attachmentId > 0 ? $attachmentId : null);
        } catch (\Throwable) {
            return $this->redirectFactory->create()->setPath('sales/guest/form');
        }
    }
}
