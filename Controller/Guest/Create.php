<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class Create implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CustomerSession $customerSession,
        private readonly MessageManagerInterface $messageManager,
        private readonly Config $config
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        if (!$this->config->allowGuestRma()) {
            return $this->redirectFactory->create()->setPath('/');
        }

        $orderId = (int)$this->customerSession->getGuestRmaOrderId();
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Please search for your order first.'));
            return $this->redirectFactory->create()->setPath('rma/guest/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('New Return Request (Guest)'));

        return $resultPage;
    }
}
