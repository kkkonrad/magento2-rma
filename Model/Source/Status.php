<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

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
        foreach (array_keys(self::LABELS) as $value) {
            $options[] = ['value' => $value, 'label' => $this->getLabel($value)];
        }
        return $options;
    }

    public function getLabel(string $status): Phrase|string
    {
        return match ($status) {
            RmaInterface::STATUS_NEW => __('Kkkonrad RMA status: new'),
            RmaInterface::STATUS_PENDING_REVIEW => __('Kkkonrad RMA status: pending review'),
            RmaInterface::STATUS_APPROVED => __('Kkkonrad RMA status: approved'),
            RmaInterface::STATUS_REJECTED => __('Kkkonrad RMA status: rejected'),
            RmaInterface::STATUS_ITEM_IN_TRANSIT => __('Kkkonrad RMA status: item in transit'),
            RmaInterface::STATUS_ITEM_RECEIVED => __('Kkkonrad RMA status: item received'),
            RmaInterface::STATUS_RESOLVED => __('Kkkonrad RMA status: resolved'),
            RmaInterface::STATUS_CLOSED => __('Kkkonrad RMA status: closed'),
            RmaInterface::STATUS_CANCELLED => __('Kkkonrad RMA status: cancelled'),
            default => $status,
        };
    }
}
