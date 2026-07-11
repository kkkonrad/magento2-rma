<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * RMA Business Logic Interface
 * @api
 */
interface RmaManagementInterface
{
    /**
     * Create a new RMA from a customer order
     *
     * @param int $orderId
     * @param int $customerId
     * @param string $resolutionType
     * @param RmaItemInterface[] $items
     * @param string|null $comment
     * @return RmaInterface
     * @throws LocalizedException
     */
    public function createFromOrder(
        int $orderId,
        int $customerId,
        string $resolutionType,
        array $items,
        ?string $comment = null,
        bool $termsAccepted = false
    ): RmaInterface;

    /**
     * Change the status of an RMA (validates transition via StatusValidator)
     *
     * @param int $rmaId
     * @param string $newStatus
     * @param string|null $comment
     * @param string $changedBy 'admin' | 'customer' | 'system'
     * @param int|null $changedById
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function changeStatus(
        int $rmaId,
        string $newStatus,
        ?string $comment = null,
        string $changedBy = 'system',
        ?int $changedById = null
    ): RmaInterface;

    /**
     * Add a message/note to an RMA
     *
     * @param int $rmaId
     * @param string $message
     * @param string $authorType
     * @param int|null $authorId
     * @param string|null $authorName
     * @param bool $isInternal
     * @return \Kkkonrad\Rma\Api\Data\RmaMessageInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addMessage(
        int $rmaId,
        string $message,
        string $authorType,
        ?int $authorId = null,
        ?string $authorName = null,
        bool $isInternal = false
    ): RmaMessageInterface;

    /**
     * Approve an RMA (moves to 'approved' status)
     * For 'refund' resolution: creates a Credit Memo
     *
     * @param int $rmaId
     * @param string|null $comment
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws LocalizedException
     */
    public function approve(int $rmaId, ?string $comment = null): RmaInterface;

    /**
     * Reject an RMA (moves to 'rejected' status)
     *
     * @param int $rmaId
     * @param string|null $comment
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws LocalizedException
     */
    public function reject(int $rmaId, ?string $comment = null): RmaInterface;

    /**
     * Cancel an RMA
     *
     * @param int $rmaId
     * @param string|null $comment
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws LocalizedException
     */
    public function cancel(int $rmaId, ?string $comment = null): RmaInterface;

    /**
     * Mark RMA as resolved and close it
     *
     * @param int $rmaId
     * @param string|null $comment
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws LocalizedException
     */
    public function resolve(int $rmaId, ?string $comment = null): RmaInterface;

    /**
     * Check if an order is eligible for RMA
     * (based on status, return window config)
     *
     * @param int $orderId
     * @param int $customerId
     * @return bool
     */
    public function isOrderEligibleForRma(int $orderId, int $customerId): bool;
}
