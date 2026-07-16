<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Helper\Guest as GuestHelper;

class Create implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CustomerSession $customerSession,
        private readonly MessageManagerInterface $messageManager,
        private readonly Config $config,
        private readonly GuestHelper $guestHelper,
        private readonly RequestInterface $request,
        private readonly Registry $registry
    ) {
    }

    public function execute(): ResultInterface
    {
        $validationResult = $this->guestHelper->loadValidOrder($this->request);
        if ($validationResult instanceof ResultInterface) {
            return $validationResult;
        }

        $order = $this->registry->registry('current_order');
        if (!$order instanceof OrderInterface
            || !$order->getCustomerIsGuest()
            || !$this->config->isEnabled((int) $order->getStoreId())
            || !$this->config->allowGuestRma((int) $order->getStoreId())
        ) {
            $this->messageManager->addErrorMessage(__('Please search for your order first.'));
            return $this->redirectFactory->create()->setPath('sales/guest/form');
        }

        $this->customerSession->setGuestRmaOrderId((int) $order->getEntityId());

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('New Return Request (Guest)'));

        return $resultPage;
    }
}
