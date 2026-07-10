<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory as RmaItemCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage as RmaMessageResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory as RmaStatusHistoryResource;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime as MagentoDateTime;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Psr\Log\LoggerInterface;

class RmaManagement implements RmaManagementInterface
{
    public function __construct(
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaItemResource $rmaItemResource,
        private readonly RmaItemCollectionFactory $rmaItemCollectionFactory,
        private readonly RmaMessageResource $rmaMessageResource,
        private readonly RmaStatusHistoryResource $rmaStatusHistoryResource,
        private readonly StatusValidator $statusValidator,
        private readonly Config $config,
        private readonly RmaFactory $rmaFactory,
        private readonly RmaItemFactory $rmaItemFactory,
        private readonly RmaMessageFactory $rmaMessageFactory,
        private readonly RmaStatusHistoryFactory $rmaStatusHistoryFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoManagementInterface $creditmemoManagement,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly EventManagerInterface $eventManager,
        private readonly MagentoDateTime $dateTime,
        private readonly LoggerInterface $logger,
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaAddress\CollectionFactory $rmaAddressCollectionFactory,
        private readonly \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private readonly \Kkkonrad\Rma\Model\RmaPolicyFactory $policyFactory,
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaPolicy $policyResource
    ) {
    }



    /**
     * @inheritDoc
     */
    public function createFromOrder(
        int $orderId,
        int $customerId,
        string $resolutionType,
        array $items,
        ?string $comment = null
    ): RmaInterface {
        // Fix 10: Load order once and reuse — eliminates the second load in isOrderEligibleForRma()
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Order not found.'));
        }

        if (!$this->isOrderEligibleForRmaObject($order, $customerId)) {
            throw new LocalizedException(__('This order is not eligible for a return.'));
        }

        // Fix 6: Race condition guard — prevent duplicate active RMAs for the same order
        $this->assertNoDuplicateActiveRma($orderId, $customerId);

        /** @var Rma $rma */
        $rma = $this->rmaFactory->create();
        $rma->setOrderId($orderId)
            ->setOrderIncrementId($order->getIncrementId())
            ->setCustomerId($customerId)
            ->setCustomerEmail($order->getCustomerEmail())
            ->setCustomerName($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname())
            ->setResolutionType($resolutionType)
            ->setComment($comment)
            ->setStatus(RmaInterface::STATUS_NEW)
            ->setStoreId((int) $order->getStoreId());

        // Assign default/active return address if exists
        $addressCollection = $this->rmaAddressCollectionFactory->create();
        $addressCollection->addFieldToFilter('is_active', 1)
            ->setOrder('is_default', 'DESC')
            ->setPageSize(1);
        $defaultAddress = $addressCollection->getFirstItem();
        if ($defaultAddress->getId()) {
            $rma->setReturnAddressId((int)$defaultAddress->getId());
        }


        $this->rmaRepository->save($rma);

        // Save items — validate each against order items (Fix 4)
        foreach ($items as $itemData) {
            $this->saveRmaItem($rma->getRmaId(), $itemData, $order);
        }

        // Add initial status history
        $this->addStatusHistory($rma->getRmaId(), null, RmaInterface::STATUS_NEW, null, 'customer', $customerId);

        // Emit event
        $this->eventManager->dispatch('kkkonrad_rma_created', [
            'rma'   => $rma,
            'order' => $order,
            'items' => $items,
        ]);

        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function changeStatus(
        int $rmaId,
        string $newStatus,
        ?string $comment = null,
        string $changedBy = 'system',
        ?int $changedById = null
    ): RmaInterface {
        $rma       = $this->rmaRepository->getById($rmaId);
        $oldStatus = $rma->getStatus();

        // Throws LocalizedException on invalid transition
        $this->statusValidator->validate($oldStatus, $newStatus);

        $rma->setStatus($newStatus);

        if ($newStatus === RmaInterface::STATUS_RESOLVED) {
            // Fix 12: Use Magento DateTime instead of PHP date()
            $rma->setResolvedAt($this->dateTime->gmtDate());
        }

        $this->rmaRepository->save($rma);

        $this->addStatusHistory($rmaId, $oldStatus, $newStatus, $comment, $changedBy, $changedById);

        // Emit event
        $this->eventManager->dispatch('kkkonrad_rma_status_changed', [
            'rma'         => $rma,
            'status_from' => $oldStatus,
            'status_to'   => $newStatus,
            'comment'     => $comment,
            'changed_by'  => $changedBy,
        ]);

        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function addMessage(
        int $rmaId,
        string $message,
        string $authorType,
        ?int $authorId = null,
        ?string $authorName = null,
        bool $isInternal = false
    ): RmaMessageInterface {
        // Fix R5: Enforce message length limit at service layer (not just controller/resolver)
        $maxLength = 5000;
        if (mb_strlen($message) > $maxLength) {
            throw new LocalizedException(__('Message cannot exceed %1 characters.', $maxLength));
        }

        // Verify the RMA exists
        $rma = $this->rmaRepository->getById($rmaId);

        /** @var RmaMessage $rmaMessage */
        $rmaMessage = $this->rmaMessageFactory->create();
        $rmaMessage->setRmaId($rmaId)
            ->setMessage($message)
            ->setAuthorType($authorType)
            ->setAuthorId($authorId)
            ->setAuthorName($authorName)
            ->setIsInternal($isInternal);

        $this->rmaMessageResource->save($rmaMessage);

        $this->eventManager->dispatch('kkkonrad_rma_message_added', [
            'rma'     => $rma,
            'message' => $rmaMessage,
        ]);

        return $rmaMessage;
    }

    /**
     * @inheritDoc
     */
    public function approve(int $rmaId, ?string $comment = null): RmaInterface
    {
        $rma = $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_APPROVED,
            $comment ?? (string) __('RMA has been approved.'),
            'admin'
        );

        // Fix 5: Create credit memo only for returned items, not the whole order
        if ($rma->getResolutionType() === RmaInterface::RESOLUTION_REFUND) {
            try {
                $this->createCreditMemo($rma);
            } catch (\Exception $e) {
                $this->logger->error('RMA credit memo creation failed: ' . $e->getMessage(), [
                    'rma_id'    => $rmaId,
                    'exception' => $e,
                ]);
                // Don't fail approval — log and continue. Admin can create CM manually.
            }
        }

        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function reject(int $rmaId, ?string $comment = null): RmaInterface
    {
        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_REJECTED,
            $comment ?? (string) __('RMA has been rejected.'),
            'admin'
        );
    }

    /**
     * @inheritDoc
     */
    public function cancel(int $rmaId, ?string $comment = null): RmaInterface
    {
        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_CANCELLED,
            $comment ?? (string) __('RMA has been cancelled.'),
            'system'
        );
    }

    /**
     * @inheritDoc
     */
    public function resolve(int $rmaId, ?string $comment = null): RmaInterface
    {
        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_RESOLVED,
            $comment ?? (string) __('RMA has been resolved.'),
            'system'
        );
    }

    /**
     * @inheritDoc
     */
    public function isOrderEligibleForRma(int $orderId, int $customerId): bool
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException) {
            return false;
        }

        return $this->isOrderEligibleForRmaObject($order, $customerId);
    }

    /**
     * Check eligibility against an already-loaded order object.
     * Fix 10: Avoids double-loading the order in createFromOrder().
     */
    private function isOrderEligibleForRmaObject(
        \Magento\Sales\Api\Data\OrderInterface $order,
        int $customerId
    ): bool {
        // Customer ownership check
        $isGuestAllowed = ($customerId === 0 && $this->config->allowGuestRma());
        if (!$isGuestAllowed && (int) $order->getCustomerId() !== $customerId) {
            return false;
        }


        // Order status check based on configuration
        $allowedStatuses = $this->config->getAllowedOrderStatuses((int) $order->getStoreId());
        if (!in_array($order->getStatus(), $allowedStatuses, true)) {
            return false;
        }

        $invoicedAt = $order->getUpdatedAt() ?? $order->getCreatedAt();
        try {
            $invoiceDate = new \DateTimeImmutable($invoicedAt);
            $now         = new \DateTimeImmutable();
        } catch (\Exception) {
            return false;
        }

        $hasEligibleItem = false;
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getParentItemId() || $orderItem->isDummy()) {
                continue;
            }

            $returnWindowDays = $this->getReturnWindowDaysForProduct($orderItem);
            $deadline = $invoiceDate->modify('+' . $returnWindowDays . ' days');
            if ($now <= $deadline) {
                $hasEligibleItem = true;
                break;
            }
        }

        return $hasEligibleItem;
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


    /**
     * Fix 6: Throw if a non-terminal RMA already exists for this order+customer combination.
     *
     * @throws LocalizedException
     */
    private function assertNoDuplicateActiveRma(int $orderId, int $customerId): void
    {
        $terminalStatuses = [
            RmaInterface::STATUS_CLOSED,
            RmaInterface::STATUS_CANCELLED,
            RmaInterface::STATUS_REJECTED,
        ];

        $collection = $this->rmaItemCollectionFactory->create();
        $collection->getSelect()->join(
            ['r' => $collection->getResource()->getTable('kkkonrad_rma')],
            'main_table.rma_id = r.rma_id',
            []
        );
        $collection->getSelect()
            ->where('r.order_id = ?', $orderId)
            ->where('r.customer_id = ?', $customerId)
            ->where('r.status NOT IN (?)', $terminalStatuses)
            ->limit(1);

        if ($collection->getSize() > 0) {
            throw new LocalizedException(
                __('An active return request already exists for this order.')
            );
        }
    }

    /**
     * Save individual RMA item from request data.
     * Fix 4: Validates that order_item_id belongs to the order and qty is within bounds.
     */
    private function saveRmaItem(
        int $rmaId,
        RmaItemInterface $itemData,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): void {
        $matchedOrderItem = null;

        foreach ($order->getItems() as $orderItem) {
            if ((int) $orderItem->getItemId() === $itemData->getOrderItemId()) {
                $matchedOrderItem = $orderItem;
                break;
            }
        }

        // Fix 4a: Verify the item belongs to this order
        if ($matchedOrderItem === null) {
            throw new LocalizedException(
                __('Item #%1 does not belong to this order.', $itemData->getOrderItemId())
            );
        }

        // Fix 4b: Validate requested qty
        $requestedQty = $itemData->getQty();
        if ($requestedQty <= 0) {
            throw new LocalizedException(__('Return quantity must be greater than zero.'));
        }

        $orderedQty = (float) $matchedOrderItem->getQtyOrdered();
        if ($requestedQty > $orderedQty) {
            throw new LocalizedException(
                __('Return quantity (%1) cannot exceed ordered quantity (%2) for "%3".',
                    $requestedQty, $orderedQty, $matchedOrderItem->getName())
            );
        }

        // Excluded SKU check — product cannot be returned if configured as excluded
        $excludedSkus = $this->config->getExcludedSkus((int) $order->getStoreId());
        if (!empty($excludedSkus) && in_array(strtoupper((string) $matchedOrderItem->getSku()), $excludedSkus, true)) {
            throw new LocalizedException(
                __('Product "%1" (SKU: %2) cannot be returned.', $matchedOrderItem->getName(), $matchedOrderItem->getSku())
            );
        }

        // Return window validation per product policy
        $invoicedAt = $order->getUpdatedAt() ?? $order->getCreatedAt();
        try {
            $invoiceDate = new \DateTimeImmutable($invoicedAt);
            $returnWindowDays = $this->getReturnWindowDaysForProduct($matchedOrderItem);
            $deadline = $invoiceDate->modify('+' . $returnWindowDays . ' days');
            $now = new \DateTimeImmutable();
            if ($now > $deadline) {
                throw new LocalizedException(
                    __('The return window for product "%1" has expired.', $matchedOrderItem->getName())
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('Failed to validate return window.'));
        }


        /** @var RmaItem $rmaItem */
        $rmaItem = $this->rmaItemFactory->create();
        $rmaItem->setRmaId($rmaId)
            ->setOrderItemId($itemData->getOrderItemId())
            ->setQty($requestedQty)
            ->setReasonId($itemData->getReasonId())
            ->setConditionId($itemData->getConditionId())
            ->setProductName($matchedOrderItem->getName())
            ->setProductSku($matchedOrderItem->getSku())
            ->setUnitPrice((float) $matchedOrderItem->getPrice());


        $this->rmaItemResource->save($rmaItem);
    }

    /**
     * Save status history entry.
     */
    private function addStatusHistory(
        int $rmaId,
        ?string $statusFrom,
        string $statusTo,
        ?string $comment,
        string $createdBy,
        ?int $createdById
    ): void {
        /** @var RmaStatusHistory $history */
        $history = $this->rmaStatusHistoryFactory->create();
        $history->setRmaId($rmaId)
            ->setStatusFrom($statusFrom)
            ->setStatusTo($statusTo)
            ->setComment($comment)
            ->setCreatedBy($createdBy)
            ->setCreatedById($createdById);

        $this->rmaStatusHistoryResource->save($history);
    }

    /**
     * Fix 5: Create credit memo only for the specific items being returned, not the whole order.
     * Uses CreditmemoFactory::createByOrder() with explicit qty mapping to avoid over-refunding.
     */
    private function createCreditMemo(RmaInterface $rma): void
    {
        $order = $this->orderRepository->get($rma->getOrderId());

        // Load RMA items to build a partial refund
        $rmaItems = $this->rmaItemCollectionFactory->create();
        $rmaItems->addFieldToFilter('rma_id', $rma->getRmaId());

        // Build qty map: [order_item_id => qty_to_refund]
        $qtys = [];
        foreach ($rmaItems as $rmaItem) {
            $qtys[$rmaItem->getOrderItemId()] = $rmaItem->getQty();
        }

        if (empty($qtys)) {
            return;
        }

        $creditmemo = $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtys]);

        if ($creditmemo) {
            $this->creditmemoManagement->refund($creditmemo);

            $this->logger->info('RMA partial credit memo created.', [
                'rma_id'   => $rma->getRmaId(),
                'order_id' => $rma->getOrderId(),
                'qtys'     => $qtys,
            ]);
        }
    }
}
