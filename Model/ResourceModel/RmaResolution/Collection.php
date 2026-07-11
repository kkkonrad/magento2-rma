<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaResolution;

use Kkkonrad\Rma\Model\RmaResolution;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution as RmaResolutionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'resolution_id';

    protected function _construct(): void
    {
        $this->_init(RmaResolution::class, RmaResolutionResource::class);
    }
}
