<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaItem;

use Kkkonrad\Rma\Model\RmaItem;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'item_id';
    protected function _construct(): void { $this->_init(RmaItem::class, RmaItemResource::class); }
}
