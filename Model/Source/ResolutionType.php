<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Framework\Data\OptionSourceInterface;

class ResolutionType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => RmaInterface::RESOLUTION_REFUND,   'label' => __('Refund')],
            ['value' => RmaInterface::RESOLUTION_EXCHANGE,  'label' => __('Exchange')],
            ['value' => RmaInterface::RESOLUTION_REPAIR,    'label' => __('Repair')],
            ['value' => RmaInterface::RESOLUTION_VOUCHER,   'label' => __('Store Voucher')],
        ];
    }
}
