<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;

class GuestRmaLocator
{
    private const TERMINAL_STATUSES = [
        RmaInterface::STATUS_CLOSED,
        RmaInterface::STATUS_CANCELLED,
        RmaInterface::STATUS_REJECTED,
    ];

    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function getLatestForOrder(int $orderId): ?RmaInterface
    {
        return $this->getForOrder($orderId, false);
    }

    public function getActiveForOrder(int $orderId): ?RmaInterface
    {
        return $this->getForOrder($orderId, true);
    }

    private function getForOrder(int $orderId, bool $activeOnly): ?RmaInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(RmaInterface::ORDER_ID, $orderId)
            ->addFieldToFilter(RmaInterface::CUSTOMER_ID, 0)
            ->setOrder(RmaInterface::RMA_ID, 'DESC')
            ->setPageSize(1);

        if ($activeOnly) {
            $collection->addFieldToFilter(RmaInterface::STATUS, ['nin' => self::TERMINAL_STATUSES]);
        }

        $rma = $collection->getFirstItem();
        return $rma->getId() ? $rma : null;
    }
}
