<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    private const LABELS = [
        RmaInterface::STATUS_NEW             => 'New',
        RmaInterface::STATUS_PENDING_REVIEW  => 'Pending Review',
        RmaInterface::STATUS_APPROVED        => 'Approved',
        RmaInterface::STATUS_REJECTED        => 'Rejected',
        RmaInterface::STATUS_ITEM_IN_TRANSIT => 'Item in Transit',
        RmaInterface::STATUS_ITEM_RECEIVED   => 'Item Received',
        RmaInterface::STATUS_RESOLVED        => 'Resolved',
        RmaInterface::STATUS_CLOSED          => 'Closed',
        RmaInterface::STATUS_CANCELLED       => 'Cancelled',
    ];

    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }
        return $options;
    }

    public function getLabel(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }
}
