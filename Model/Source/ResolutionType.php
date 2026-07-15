<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\DictionaryLabelTranslator;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class ResolutionType implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $resolutionCollectionFactory,
        private readonly DictionaryLabelTranslator $dictionaryLabelTranslator
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
                'label' => $this->dictionaryLabelTranslator->getResolutionLabel(
                    (string) $resolution->getCode(),
                    (string) $resolution->getLabel()
                ),
            ];
        }

        if (empty($options)) {
            // Fallback defaults
            return [
                $this->getDefaultOption(RmaInterface::RESOLUTION_REFUND, 'Refund'),
                $this->getDefaultOption(RmaInterface::RESOLUTION_EXCHANGE, 'Exchange'),
                $this->getDefaultOption(RmaInterface::RESOLUTION_REPAIR, 'Repair'),
                $this->getDefaultOption(RmaInterface::RESOLUTION_VOUCHER, 'Store Voucher'),
            ];
        }

        return $options;
    }

    private function getDefaultOption(string $code, string $fallbackLabel): array
    {
        return [
            'value' => $code,
            'label' => $this->dictionaryLabelTranslator->getResolutionLabel($code, $fallbackLabel),
        ];
    }
}
