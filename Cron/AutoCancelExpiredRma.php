<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Cron;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Auto-cancel RMAs that have been waiting for customer response too long.
 */
class AutoCancelExpiredRma
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly StoreManagerInterface $storeManager,
        private readonly DateTime $dateTime
    ) {
    }

    public function execute(): void
    {
        $cancelled = 0;
        $errors    = 0;

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $autoCancelDays = $this->config->getAutoCancelDays($storeId);
            if ($autoCancelDays <= 0) {
                continue;
            }

            $cutoffDate = $this->dateTime->gmtDate(
                'Y-m-d H:i:s',
                $this->dateTime->gmtTimestamp() - ($autoCancelDays * 86400)
            );
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('status', ['eq' => RmaInterface::STATUS_PENDING_REVIEW])
                ->addFieldToFilter('updated_at', ['lteq' => $cutoffDate]);

            foreach ($collection as $rma) {
                try {
                    $this->rmaManagement->cancel(
                        (int) $rma->getRmaId(),
                        (string) __('Automatically cancelled due to no customer response within %1 days.', $autoCancelDays)
                    );
                    $cancelled++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error('Failed to auto-cancel RMA ' . $rma->getRmaId() . ': ' . $e->getMessage());
                }
            }
        }

        if ($cancelled > 0 || $errors > 0) {
            $this->logger->info("RMA auto-cancel cron: cancelled={$cancelled}, errors={$errors}");
        }
    }
}
