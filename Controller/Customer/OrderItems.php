<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Customer;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * AJAX endpoint: returns order items for a given order_id
 * Only returns items if the order belongs to the logged-in customer.
 */
class OrderItems implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RequestInterface $request
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['error' => true, 'message' => 'Please log in.']);
        }

        $orderId    = (int) $this->request->getParam('order_id');
        $customerId = (int) $this->customerSession->getCustomerId();

        try {
            $order = $this->orderRepository->get($orderId);

            if ((int) $order->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Order not found.'));
            }

            if (!$this->rmaManagement->isOrderEligibleForRma($orderId, $customerId)) {
                throw new LocalizedException(__('This order is not eligible for a return.'));
            }

            $items = [];
            foreach ($order->getItems() as $item) {
                if ($item->getParentItemId() || $item->isDummy()) {
                    continue;
                }
                $items[] = [
                    'item_id'      => (int) $item->getItemId(),
                    'name'         => $item->getName(),
                    'sku'          => $item->getSku(),
                    'qty_ordered'  => (float) $item->getQtyOrdered(),
                    'qty_refunded' => (float) $item->getQtyRefunded(),
                    'price'        => (float) $item->getPrice(),
                ];
            }

            return $result->setData(['error' => false, 'items' => $items]);
        } catch (LocalizedException $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => 'An error occurred.']);
        }
    }
}
