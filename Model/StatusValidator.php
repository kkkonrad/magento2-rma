<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * State Machine Validator for RMA status transitions.
 * Throws LocalizedException on invalid transitions — never swallows errors silently.
 */
class StatusValidator
{
    /**
     * Allowed transitions: [from_status => [allowed_to_statuses]]
     */
    private const ALLOWED_TRANSITIONS = [
        RmaInterface::STATUS_NEW => [
            RmaInterface::STATUS_PENDING_REVIEW,
            RmaInterface::STATUS_CANCELLED,
        ],
        RmaInterface::STATUS_PENDING_REVIEW => [
            RmaInterface::STATUS_APPROVED,
            RmaInterface::STATUS_REJECTED,
            RmaInterface::STATUS_CANCELLED,
        ],
        RmaInterface::STATUS_APPROVED => [
            RmaInterface::STATUS_ITEM_IN_TRANSIT,
            RmaInterface::STATUS_CANCELLED,
        ],
        RmaInterface::STATUS_REJECTED => [
            RmaInterface::STATUS_CLOSED,
        ],
        RmaInterface::STATUS_ITEM_IN_TRANSIT => [
            RmaInterface::STATUS_ITEM_RECEIVED,
        ],
        RmaInterface::STATUS_ITEM_RECEIVED => [
            RmaInterface::STATUS_RESOLVED,
            // Fix R8: Allow rejection after item is received (e.g. returned item was not eligible)
            RmaInterface::STATUS_REJECTED,
        ],
        RmaInterface::STATUS_RESOLVED => [
            RmaInterface::STATUS_CLOSED,
        ],
        RmaInterface::STATUS_CLOSED    => [],
        RmaInterface::STATUS_CANCELLED => [],
    ];

    /**
     * All valid statuses
     */
    private const VALID_STATUSES = [
        RmaInterface::STATUS_NEW,
        RmaInterface::STATUS_PENDING_REVIEW,
        RmaInterface::STATUS_APPROVED,
        RmaInterface::STATUS_REJECTED,
        RmaInterface::STATUS_ITEM_IN_TRANSIT,
        RmaInterface::STATUS_ITEM_RECEIVED,
        RmaInterface::STATUS_RESOLVED,
        RmaInterface::STATUS_CLOSED,
        RmaInterface::STATUS_CANCELLED,
    ];

    /**
     * Validate a status transition.
     *
     * @throws LocalizedException if the transition is not allowed
     */
    public function validate(string $fromStatus, string $toStatus): void
    {
        if (!in_array($toStatus, self::VALID_STATUSES, true)) {
            throw new LocalizedException(
                __('Invalid RMA status "%1". Valid statuses are: %2', $toStatus, implode(', ', self::VALID_STATUSES))
            );
        }

        if (!isset(self::ALLOWED_TRANSITIONS[$fromStatus])) {
            throw new LocalizedException(
                __('Unknown current RMA status "%1".', $fromStatus)
            );
        }

        $allowed = self::ALLOWED_TRANSITIONS[$fromStatus];

        if (!in_array($toStatus, $allowed, true)) {
            throw new LocalizedException(
                __(
                    'Cannot transition RMA from "%1" to "%2". Allowed transitions from "%1": %3',
                    $fromStatus,
                    $toStatus,
                    empty($allowed) ? __('none (terminal state)') : implode(', ', $allowed)
                )
            );
        }
    }

    /**
     * Check if a transition is allowed without throwing.
     */
    public function isTransitionAllowed(string $fromStatus, string $toStatus): bool
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$fromStatus])) {
            return false;
        }
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus], true);
    }

    /**
     * Get all statuses allowed from a given status.
     *
     * @return string[]
     */
    public function getAllowedTransitions(string $fromStatus): array
    {
        return self::ALLOWED_TRANSITIONS[$fromStatus] ?? [];
    }

    /**
     * Get all valid statuses.
     *
     * @return string[]
     */
    public function getAllStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    /**
     * Check if a status is a terminal state (no further transitions allowed).
     */
    public function isTerminalStatus(string $status): bool
    {
        return isset(self::ALLOWED_TRANSITIONS[$status]) && empty(self::ALLOWED_TRANSITIONS[$status]);
    }
}
