<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class SearchOrder extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_create';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();
        $incrementId = trim((string)$this->getRequest()->getParam('increment_id'));

        if (!$incrementId) {
            return $result->setData(['error' => true, 'message' => __('Order ID is required.')]);
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
            $order = reset($orders);

            if (!$order) {
                return $result->setData(['error' => true, 'message' => __('Order not found.')]);
            }

            // Status verification
            $allowedStatuses = $this->config->getAllowedOrderStatuses((int) $order->getStoreId());
            if (!in_array($order->getStatus(), $allowedStatuses, true)) {
                return $result->setData([
                    'error' => true,
                    'message' => __('Order status "%1" is not eligible for returns.', $order->getStatus())
                ]);
            }

            $items = [];
            foreach ($order->getItems() as $item) {
                if ($item->getParentItemId() || $item->isDummy()) {
                    continue;
                }

                $qtyOrdered = (float)$item->getQtyOrdered();
                $qtyRefunded = (float)$item->getQtyRefunded();
                $qtyAvailable = max(0, $qtyOrdered - $qtyRefunded);

                $items[] = [
                    'item_id' => (int)$item->getItemId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'qty_ordered' => $qtyOrdered,
                    'qty_refunded' => $qtyRefunded,
                    'qty_available' => $qtyAvailable,
                    'price' => (float)$item->getPrice()
                ];
            }

            return $result->setData([
                'error' => false,
                'order_id' => (int)$order->getEntityId(),
                'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'customer_email' => $order->getCustomerEmail(),
                'items' => $items
            ]);

        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
