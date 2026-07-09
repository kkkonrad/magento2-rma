<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\Rma;

use Kkkonrad\Rma\Model\Rma;
use Kkkonrad\Rma\Model\ResourceModel\Rma as RmaResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'rma_id';
    protected $_eventPrefix = 'kkkonrad_rma_collection';
    protected $_eventObject = 'rma_collection';

    protected function _construct(): void
    {
        $this->_init(Rma::class, RmaResource::class);
    }
}
