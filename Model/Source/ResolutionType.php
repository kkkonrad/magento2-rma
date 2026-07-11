<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Data\OptionSourceInterface;

class ResolutionType implements OptionSourceInterface
{
    public function __construct(
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaResolution\CollectionFactory $resolutionCollectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $collection = $this->resolutionCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', 'ASC');

        $options = [];
        foreach ($collection as $resolution) {
            $options[] = [
                'value' => $resolution->getCode(),
                'label' => __($resolution->getLabel())
            ];
        }

        if (empty($options)) {
            // Fallback defaults
            return [
                ['value' => RmaInterface::RESOLUTION_REFUND,   'label' => __('Refund')],
                ['value' => RmaInterface::RESOLUTION_EXCHANGE,  'label' => __('Exchange')],
                ['value' => RmaInterface::RESOLUTION_REPAIR,    'label' => __('Repair')],
                ['value' => RmaInterface::RESOLUTION_VOUCHER,   'label' => __('Store Voucher')],
            ];
        }

        return $options;
    }
}

