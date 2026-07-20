<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class PrintSlip implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly RequestInterface $request,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly GuestAccessToken $guestAccessToken
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $rmaId = (int) $this->request->getParam('rma_id');
        $token = (string) $this->request->getParam('hash');
        try {
            $rma = $this->rmaRepository->getById($rmaId);
            if ((int) $rma->getCustomerId() !== 0 || !$this->guestAccessToken->isValid($rma, $token)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Access denied.'));
            }
        } catch (\Throwable) {
            return $this->redirectFactory->create()->setPath('sales/guest/form');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Return Slip - RMA #%1', $rma->getIncrementId()));
        return $page;
    }
}
