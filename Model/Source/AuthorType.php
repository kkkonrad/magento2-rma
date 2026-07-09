<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Magento\Framework\Data\OptionSourceInterface;

class AuthorType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => RmaMessageInterface::AUTHOR_ADMIN,    'label' => __('Admin')],
            ['value' => RmaMessageInterface::AUTHOR_CUSTOMER, 'label' => __('Customer')],
            ['value' => RmaMessageInterface::AUTHOR_SYSTEM,   'label' => __('System')],
        ];
    }
}
