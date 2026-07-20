<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

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
        private readonly RequestInterface $request,
        private readonly \Kkkonrad\Rma\Model\Config $config,
        private readonly \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private readonly \Kkkonrad\Rma\Model\RmaPolicyFactory $policyFactory,
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaPolicy $policyResource,
        private readonly \Kkkonrad\Rma\Model\InvoiceDateProvider $invoiceDateProvider
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        $orderId = (int) $this->request->getParam('order_id');
        $customerId = (int) $this->customerSession->getCustomerId();

        $isAuthorized = false;
        if ($this->customerSession->isLoggedIn()) {
            $isAuthorized = true;
        } elseif ($this->config->allowGuestRma() && (int)$this->customerSession->getGuestRmaOrderId() === $orderId) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return $result->setData(['error' => true, 'message' => __('Access denied.')]);
        }

        try {
            $order = $this->orderRepository->get($orderId);

            if ($this->customerSession->isLoggedIn() && (int) $order->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Order not found.'));
            }


            if (!$this->rmaManagement->isOrderEligibleForRma($orderId, $customerId)) {
                throw new LocalizedException(__('This order is not eligible for a return.'));
            }

            $excludedSkus = $this->config->getExcludedSkus((int) $order->getStoreId());
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            $items = [];
            foreach ($order->getItems() as $item) {
                if ($item->getParentItemId() || $item->isDummy()) {

                    continue;
                }

                $qtyOrdered  = (float) $item->getQtyOrdered();
                $qtyRefunded = (float) $item->getQtyRefunded();
                // Fix R9: Expose available qty so frontend can cap the return quantity correctly
                $qtyAvailable = max(0, $qtyOrdered - $qtyRefunded);
                $sku = $item->getSku();
                $isExcluded = !empty($excludedSkus) && in_array(strtoupper((string) $sku), $excludedSkus, true);

                // Calculate product specific return window
                $returnWindowDays = $this->getReturnWindowDaysForProduct($item);
                $invoiceDate = $this->invoiceDateProvider->getLatestInvoiceDate(
                    $order,
                    (int) $item->getItemId()
                );
                $isExpired = $invoiceDate === null
                    || $now > $invoiceDate->modify('+' . $returnWindowDays . ' days');

                $items[] = [
                    'item_id'       => (int) $item->getItemId(),
                    'name'          => $item->getName(),
                    'sku'           => $sku,
                    'qty_ordered'   => $qtyOrdered,
                    'qty_refunded'  => $qtyRefunded,
                    'qty_available' => $qtyAvailable,
                    'price'         => (float) $item->getPrice(),
                    'is_excluded'   => $isExcluded,
                    'is_expired'    => $isExpired,
                ];
            }

            return $result->setData(['error' => false, 'items' => $items]);
        } catch (LocalizedException $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => 'An error occurred.']);
        }
    }

    /**
     * Get return window days for a specific order item.
     */
    private function getReturnWindowDaysForProduct(\Magento\Sales\Api\Data\OrderItemInterface $orderItem): int
    {
        $storeId = (int)$orderItem->getStoreId();
        try {
            $product = $this->productRepository->getById((int)$orderItem->getProductId());
            $policyIdAttr = $product->getCustomAttribute('kkkonrad_rma_policy_id');
            $policyId = $policyIdAttr ? (int)$policyIdAttr->getValue() : null;
            if ($policyId) {
                /** @var \Kkkonrad\Rma\Model\RmaPolicy $policy */
                $policy = $this->policyFactory->create();
                $this->policyResource->load($policy, $policyId);
                if ($policy->getPolicyId() && $policy->getIsActive()) {
                    return max(
                        $policy->getDaysRefund(),
                        $policy->getDaysExchange(),
                        $policy->getDaysRepair(),
                        $policy->getDaysVoucher()
                    );
                }
            }
        } catch (\Exception) {
            // Fallback to global
        }
        return $this->config->getReturnWindowDays($storeId);
    }
}
