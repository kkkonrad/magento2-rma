<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class View implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly RequestInterface $request,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly MessageManagerInterface $messageManager,
        private readonly Config $config,
        private readonly GuestAccessToken $guestAccessToken
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        if (!$this->config->allowGuestRma()) {
            return $this->redirectFactory->create()->setPath('/');
        }

        $rmaId = (int)$this->request->getParam('rma_id');
        $hash  = (string)$this->request->getParam('hash');

        try {
            $rma = $this->rmaRepository->getById($rmaId);

            // Secure Hash Authentication (avoids IDOR vulnerabilities)
            if (!$this->guestAccessToken->isValid($rma, $hash)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Access denied. Invalid tracking link.'));
            }

            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(
                __('Return Request Details (Guest) #%1', $rma->getIncrementId())
            );

            return $resultPage;

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->redirectFactory->create()->setPath('sales/guest/form');
        }
    }
}
