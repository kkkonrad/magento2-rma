<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;

class InvoiceDateProvider
{
    public function __construct(private readonly InvoiceCollectionFactory $invoiceCollectionFactory)
    {
    }

    public function getLatestInvoiceDate(OrderInterface $order, ?int $orderItemId = null): ?\DateTimeImmutable
    {
        $collection = $this->invoiceCollectionFactory->create();
        $collection->addFieldToFilter('order_id', (int) $order->getEntityId())
            ->setOrder('created_at', 'DESC');

        foreach ($collection as $invoice) {
            if ($orderItemId !== null && !$this->containsOrderItem($invoice->getAllItems(), $orderItemId)) {
                continue;
            }

            try {
                return new \DateTimeImmutable(
                    (string) $invoice->getCreatedAt(),
                    new \DateTimeZone('UTC')
                );
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    /** @param iterable<\Magento\Sales\Api\Data\InvoiceItemInterface> $invoiceItems */
    private function containsOrderItem(iterable $invoiceItems, int $orderItemId): bool
    {
        foreach ($invoiceItems as $invoiceItem) {
            if ((int) $invoiceItem->getOrderItemId() === $orderItemId) {
                return true;
            }
        }
        return false;
    }
}
