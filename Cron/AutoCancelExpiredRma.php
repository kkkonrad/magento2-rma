<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Cron;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Auto-cancel RMAs that have been waiting for customer response too long.
 */
class AutoCancelExpiredRma
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $autoCancelDays = $this->config->getAutoCancelDays();

        if ($autoCancelDays <= 0) {
            return;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . $autoCancelDays . ' days'));

        // Find RMAs in pending_review status older than cutoff
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => RmaInterface::STATUS_PENDING_REVIEW])
            ->addFieldToFilter('updated_at', ['lteq' => $cutoffDate]);

        $cancelled = 0;
        $errors    = 0;

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

        if ($cancelled > 0 || $errors > 0) {
            $this->logger->info("RMA auto-cancel cron: cancelled={$cancelled}, errors={$errors}");
        }
    }
}
