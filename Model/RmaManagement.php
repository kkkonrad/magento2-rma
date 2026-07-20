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
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as RmaReasonResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as RmaConditionResource;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Lock\LockManagerInterface;
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
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaPolicy $policyResource,
        private readonly ResourceConnection $resourceConnection,
        private readonly RmaReasonFactory $rmaReasonFactory,
        private readonly RmaReasonResource $rmaReasonResource,
        private readonly RmaConditionFactory $rmaConditionFactory,
        private readonly RmaConditionResource $rmaConditionResource,
        private readonly InvoiceDateProvider $invoiceDateProvider,
        private readonly LockManagerInterface $lockManager
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
        ?string $comment = null,
        bool $termsAccepted = false,
        bool $hasAttachments = false,
        bool $dispatchEvent = true
    ): RmaInterface {
        if ($items === []) {
            throw new LocalizedException(__('A return request must contain at least one item.'));
        }
        foreach ($items as $item) {
            if (!$item instanceof RmaItemInterface) {
                throw new LocalizedException(__('Invalid return item data.'));
            }
        }

        // Fix 10: Load order once and reuse — eliminates the second load in isOrderEligibleForRma()
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Order not found.'));
        }

        if (!$this->isOrderEligibleForRmaObject($order, $customerId)) {
            throw new LocalizedException(__('This order is not eligible for a return.'));
        }
        if (!$this->config->isEnabled((int) $order->getStoreId())) {
            throw new LocalizedException(__('RMA is currently unavailable.'));
        }
        if ($this->config->isTermsEnabled((int) $order->getStoreId()) && !$termsAccepted) {
            throw new LocalizedException(__('You must accept the return terms and conditions.'));
        }
        if (!in_array($resolutionType, [
            RmaInterface::RESOLUTION_REFUND,
            RmaInterface::RESOLUTION_EXCHANGE,
            RmaInterface::RESOLUTION_REPAIR,
            RmaInterface::RESOLUTION_VOUCHER,
        ], true)) {
            throw new LocalizedException(__('Invalid resolution type.'));
        }

        $lockName = sprintf('kkkonrad_rma_create_%d_%d', $orderId, $customerId);
        if (!$this->lockManager->lock($lockName, 10)) {
            throw new LocalizedException(__('Another return request for this order is being processed.'));
        }

        try {
            $this->assertNoDuplicateActiveRma($orderId, $customerId);

        $orderItemIds = [];
        foreach ($items as $itemData) {
            $orderItemId = $itemData->getOrderItemId();
            if (isset($orderItemIds[$orderItemId])) {
                throw new LocalizedException(__('An order item can only be included once in a return request.'));
            }
            $orderItemIds[$orderItemId] = true;
        }

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


            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();
            try {
                $rma->setIncrementId($this->getNextIncrementId($connection));
                $this->rmaRepository->save($rma);
                foreach ($items as $itemData) {
                    $this->saveRmaItem(
                        $rma->getRmaId(),
                        $itemData,
                        $order,
                        $resolutionType,
                        $hasAttachments
                    );
                }
                $this->addStatusHistory($rma->getRmaId(), null, RmaInterface::STATUS_NEW, null, 'customer', $customerId);
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
        } finally {
            $this->lockManager->unlock($lockName);
        }

        if ($dispatchEvent) {
            $this->eventManager->dispatch('kkkonrad_rma_created', [
                'rma'   => $rma,
                'order' => $order,
                'items' => $items,
            ]);
        }

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
        $lockName = 'kkkonrad_rma_status_' . $rmaId;
        if (!$this->lockManager->lock($lockName, 10)) {
            throw new LocalizedException(__('This RMA is currently being updated.'));
        }

        try {
            return $this->changeStatusUnlocked($rmaId, $newStatus, $comment, $changedBy, $changedById);
        } finally {
            $this->lockManager->unlock($lockName);
        }
    }

    private function changeStatusUnlocked(
        int $rmaId,
        string $newStatus,
        ?string $comment,
        string $changedBy,
        ?int $changedById
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

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $this->rmaRepository->save($rma);
            $this->addStatusHistory($rmaId, $oldStatus, $newStatus, $comment, $changedBy, $changedById);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

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
        $message = trim($message);
        if ($message === '') {
            throw new LocalizedException(__('Message cannot be empty.'));
        }
        if (!in_array($authorType, [
            RmaMessageInterface::AUTHOR_ADMIN,
            RmaMessageInterface::AUTHOR_CUSTOMER,
            RmaMessageInterface::AUTHOR_SYSTEM,
        ], true)) {
            throw new LocalizedException(__('Invalid message author type.'));
        }

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

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $this->rmaMessageResource->save($rmaMessage);
            $rma->setUpdatedAt($this->dateTime->gmtDate());
            $this->rmaRepository->save($rma);
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

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
        $lockName = 'kkkonrad_rma_status_' . $rmaId;
        if (!$this->lockManager->lock($lockName, 10)) {
            throw new LocalizedException(__('This RMA is already being approved.'));
        }

        try {
            $rma = $this->rmaRepository->getById($rmaId);
            $this->statusValidator->validate($rma->getStatus(), RmaInterface::STATUS_APPROVED);

            if ($rma->getResolutionType() === RmaInterface::RESOLUTION_REFUND) {
                try {
                    $this->createCreditMemo($rma);
                } catch (\Throwable $e) {
                    $this->logger->error('RMA credit memo creation failed: ' . $e->getMessage(), [
                        'rma_id'    => $rmaId,
                        'exception' => $e,
                    ]);
                    throw new LocalizedException(
                        __('The RMA was not approved because the credit memo could not be created: %1', $e->getMessage()),
                        $e
                    );
                }
            }

            return $this->changeStatusUnlocked(
                $rmaId,
                RmaInterface::STATUS_APPROVED,
                $comment ?? (string) __('RMA has been approved.'),
                'admin',
                null
            );
        } finally {
            $this->lockManager->unlock($lockName);
        }
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
    public function cancel(
        int $rmaId,
        ?string $comment = null,
        string $changedBy = 'system',
        ?int $changedById = null
    ): RmaInterface
    {
        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_CANCELLED,
            $comment ?? (string) __('RMA has been cancelled.'),
            $changedBy,
            $changedById
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
        $isGuestAllowed = $customerId === 0
            && $order->getCustomerIsGuest()
            && $this->config->allowGuestRma((int) $order->getStoreId());
        if (!$isGuestAllowed && (int) $order->getCustomerId() !== $customerId) {
            return false;
        }

        // Customer group eligibility check
        $customerGroupId = $order->getCustomerGroupId() !== null ? (int)$order->getCustomerGroupId() : null;
        if ($customerGroupId !== null) {
            $excludedGroups = $this->config->getExcludedCustomerGroups((int)$order->getStoreId());
            if (in_array($customerGroupId, $excludedGroups, true)) {
                return false;
            }
        }

        // Order status check based on configuration

        $allowedStatuses = $this->config->getAllowedOrderStatuses((int) $order->getStoreId());
        if (!in_array($order->getStatus(), $allowedStatuses, true)) {
            return false;
        }

        $invoiceDate = $this->invoiceDateProvider->getLatestInvoiceDate($order);
        if ($invoiceDate === null) {
            return false;
        }
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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
    private function getReturnWindowDaysForProduct(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        ?string $resolutionType = null
    ): int
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
                    return match ($resolutionType) {
                        RmaInterface::RESOLUTION_REFUND => $policy->getDaysRefund(),
                        RmaInterface::RESOLUTION_EXCHANGE => $policy->getDaysExchange(),
                        RmaInterface::RESOLUTION_REPAIR => $policy->getDaysRepair(),
                        RmaInterface::RESOLUTION_VOUCHER => $policy->getDaysVoucher(),
                        default => max(
                            $policy->getDaysRefund(),
                            $policy->getDaysExchange(),
                            $policy->getDaysRepair(),
                            $policy->getDaysVoucher()
                        ),
                    };
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
        \Magento\Sales\Api\Data\OrderInterface $order,
        string $resolutionType,
        bool $hasAttachments
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

        if ($matchedOrderItem->getParentItemId() || $matchedOrderItem->isDummy()) {
            throw new LocalizedException(__('The selected order item cannot be returned directly.'));
        }

        // Fix 4b: Validate requested qty
        $requestedQty = $itemData->getQty();
        if ($requestedQty <= 0) {
            throw new LocalizedException(__('Return quantity must be greater than zero.'));
        }

        $availableQty = max(0.0, (float) $matchedOrderItem->getQtyOrdered() - (float) $matchedOrderItem->getQtyRefunded());
        if ($requestedQty > $availableQty) {
            throw new LocalizedException(
                __('Return quantity (%1) cannot exceed available quantity (%2) for "%3".',
                    $requestedQty, $availableQty, $matchedOrderItem->getName())
            );
        }

        $reason = $this->validateDictionaryValue(
            $itemData->getReasonId(),
            $this->rmaReasonFactory,
            $this->rmaReasonResource,
            'reason_id',
            __('Invalid or inactive return reason.')
        );
        if ((bool) $reason->getData('require_image') && !$hasAttachments) {
            throw new LocalizedException(
                __('At least one selected return reason requires an attachment.')
            );
        }
        $this->validateDictionaryValue(
            $itemData->getConditionId(),
            $this->rmaConditionFactory,
            $this->rmaConditionResource,
            'condition_id',
            __('Invalid or inactive item condition.')
        );

        // Excluded SKU check — product cannot be returned if configured as excluded
        $excludedSkus = $this->config->getExcludedSkus((int) $order->getStoreId());
        if (!empty($excludedSkus) && in_array(strtoupper((string) $matchedOrderItem->getSku()), $excludedSkus, true)) {
            throw new LocalizedException(
                __('Product "%1" (SKU: %2) cannot be returned.', $matchedOrderItem->getName(), $matchedOrderItem->getSku())
            );
        }

        // Return window validation per product policy
        $invoiceDate = $this->invoiceDateProvider->getLatestInvoiceDate(
            $order,
            (int) $matchedOrderItem->getItemId()
        );
        if ($invoiceDate === null) {
            throw new LocalizedException(__('No invoice was found for product "%1".', $matchedOrderItem->getName()));
        }
        try {
            $returnWindowDays = $this->getReturnWindowDaysForProduct($matchedOrderItem, $resolutionType);
            $deadline = $invoiceDate->modify('+' . $returnWindowDays . ' days');
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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

    private function validateDictionaryValue(
        ?int $id,
        object $factory,
        object $resource,
        string $idField,
        \Magento\Framework\Phrase $errorMessage
    ): object {
        $item = $factory->create();
        if ($id !== null) {
            $resource->load($item, $id, $idField);
        }
        if (!(int) $item->getData('is_active')) {
            throw new LocalizedException($errorMessage);
        }
        return $item;
    }

    private function getNextIncrementId(\Magento\Framework\DB\Adapter\AdapterInterface $connection): string
    {
        $sequenceTable = $this->resourceConnection->getTableName('kkkonrad_rma_sequence');
        $connection->insert($sequenceTable, []);
        $sequenceValue = (int)$connection->fetchOne('SELECT LAST_INSERT_ID()');

        return sprintf('RMA-%06d', $sequenceValue);
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
            throw new LocalizedException(__('The RMA does not contain refundable items.'));
        }

        $creditmemo = $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtys]);

        if (!$creditmemo) {
            throw new LocalizedException(__('The credit memo could not be prepared.'));
        }

        $this->creditmemoManagement->refund($creditmemo);

        $this->logger->info('RMA partial credit memo created.', [
            'rma_id'   => $rma->getRmaId(),
            'order_id' => $rma->getOrderId(),
            'qtys'     => $qtys,
        ]);
    }

}
