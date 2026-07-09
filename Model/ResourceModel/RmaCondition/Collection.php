<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaCondition;

use Kkkonrad\Rma\Model\RmaCondition;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as RmaConditionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'condition_id';
    protected function _construct(): void { $this->_init(RmaCondition::class, RmaConditionResource::class); }
}
