<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage as RmaMessageResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory as RmaStatusHistoryResource;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoCommentInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterfaceFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Psr\Log\LoggerInterface;

class RmaManagement implements RmaManagementInterface
{
    public function __construct(
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaItemResource $rmaItemResource,
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
        private readonly LoggerInterface $logger
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
        if (!$this->isOrderEligibleForRma($orderId, $customerId)) {
            throw new LocalizedException(__('This order is not eligible for a return.'));
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Order not found.'));
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

        $this->rmaRepository->save($rma);

        // Save items
        foreach ($items as $itemData) {
            $this->saveRmaItem($rma->getRmaId(), $itemData, $order);
        }

        // Add initial status history
        $this->addStatusHistory($rma->getRmaId(), null, RmaInterface::STATUS_NEW, null, 'customer', $customerId);

        // Emit event
        $this->eventManager->dispatch('kkkonrad_rma_created', [
            'rma'       => $rma,
            'order'     => $order,
            'items'     => $items,
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
        $rma = $this->rmaRepository->getById($rmaId);
        $oldStatus = $rma->getStatus();

        // Throws LocalizedException on invalid transition
        $this->statusValidator->validate($oldStatus, $newStatus);

        $rma->setStatus($newStatus);

        if ($newStatus === RmaInterface::STATUS_RESOLVED) {
            $rma->setResolvedAt(date('Y-m-d H:i:s'));
        }

        $this->rmaRepository->save($rma);

        $this->addStatusHistory($rmaId, $oldStatus, $newStatus, $comment, $changedBy, $changedById);

        // Emit event
        $this->eventManager->dispatch('kkkonrad_rma_status_changed', [
            'rma'        => $rma,
            'status_from' => $oldStatus,
            'status_to'  => $newStatus,
            'comment'    => $comment,
            'changed_by' => $changedBy,
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

        // Create credit memo for refund resolutions
        if ($rma->getResolutionType() === RmaInterface::RESOLUTION_REFUND) {
            try {
                $this->createCreditMemo($rma);
            } catch (\Exception $e) {
                $this->logger->error('RMA credit memo creation failed: ' . $e->getMessage(), [
                    'rma_id'   => $rmaId,
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

        // Customer ownership check
        if ((int) $order->getCustomerId() !== $customerId) {
            return false;
        }

        // Order status check based on configuration
        $allowedStatuses = $this->config->getAllowedOrderStatuses((int) $order->getStoreId());
        if (!in_array($order->getStatus(), $allowedStatuses, true)) {
            return false;
        }

        // Check return window (configured in days)
        $returnWindowDays = $this->config->getReturnWindowDays((int) $order->getStoreId());
        $invoicedAt = $order->getUpdatedAt() ?? $order->getCreatedAt();
        $deadline = strtotime('+' . $returnWindowDays . ' days', strtotime($invoicedAt));

        if (time() > $deadline) {
            return false;
        }

        return true;
    }

    /**
     * Save individual RMA item from request data
     */
    private function saveRmaItem(int $rmaId, RmaItemInterface $itemData, \Magento\Sales\Api\Data\OrderInterface $order): void
    {
        /** @var RmaItem $rmaItem */
        $rmaItem = $this->rmaItemFactory->create();
        $rmaItem->setRmaId($rmaId)
            ->setOrderItemId($itemData->getOrderItemId())
            ->setQty($itemData->getQty())
            ->setReasonId($itemData->getReasonId())
            ->setConditionId($itemData->getConditionId());

        // Enrich with product data from the order item
        foreach ($order->getItems() as $orderItem) {
            if ((int) $orderItem->getItemId() === $itemData->getOrderItemId()) {
                $rmaItem->setProductName($orderItem->getName())
                    ->setProductSku($orderItem->getSku())
                    ->setUnitPrice((float) $orderItem->getPrice());
                break;
            }
        }

        $this->rmaItemResource->save($rmaItem);
    }

    /**
     * Save status history entry
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
     * Create credit memo for refund-type RMA approval.
     */
    private function createCreditMemo(RmaInterface $rma): void
    {
        $order = $this->orderRepository->get($rma->getOrderId());
        $creditmemo = $this->creditmemoFactory->createByOrder($order);

        if ($creditmemo) {
            $this->creditmemoManagement->refund($creditmemo);

            $this->logger->info('RMA credit memo created.', [
                'rma_id'   => $rma->getRmaId(),
                'order_id' => $rma->getOrderId(),
            ]);
        }
    }
}
