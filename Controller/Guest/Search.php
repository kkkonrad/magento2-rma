<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Search implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly CustomerSession $customerSession,
        private readonly MessageManagerInterface $messageManager,
        private readonly Config $config
    ) {
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->redirectFactory->create();

        if (!$this->config->allowGuestRma()) {
            return $resultRedirect->setPath('/');
        }

        $incrementId = trim((string)$this->request->getPost('order_id'));
        $email       = trim((string)$this->request->getPost('email'));
        $lastname    = trim((string)$this->request->getPost('lastname'));

        if (!$incrementId || !$email || !$lastname) {
            $this->messageManager->addErrorMessage(__('Please fill in all search fields.'));
            return $resultRedirect->setPath('rma/guest/index');
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
            $order = reset($orders);

            if (!$order) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order not found.'));
            }

            // Verify billing address last name and email
            $billingAddress = $order->getBillingAddress();
            $orderLastname = $billingAddress ? $billingAddress->getLastname() : $order->getCustomerLastname();
            $orderEmail = $order->getCustomerEmail();

            if (strcasecmp((string)$orderEmail, $email) !== 0 || strcasecmp((string)$orderLastname, $lastname) !== 0) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The order details do not match our records.'));
            }

            // Store order ID in guest session to authorize return creation
            $this->customerSession->setGuestRmaOrderId((int)$order->getEntityId());

            return $resultRedirect->setPath('rma/guest/create');

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('rma/guest/index');
        }
    }
}
