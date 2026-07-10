<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaAddress;

use Kkkonrad\Rma\Model\RmaAddress;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as RmaAddressResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'address_id';

    protected function _construct(): void
    {
        $this->_init(RmaAddress::class, RmaAddressResource::class);
    }
}
