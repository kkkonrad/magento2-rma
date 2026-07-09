<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory;

use Kkkonrad\Rma\Model\RmaStatusHistory;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory as RmaStatusHistoryResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'history_id';
    protected function _construct(): void { $this->_init(RmaStatusHistory::class, RmaStatusHistoryResource::class); }
}
