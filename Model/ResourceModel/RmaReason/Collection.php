<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaReason;

use Kkkonrad\Rma\Model\RmaReason;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as RmaReasonResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'reason_id';
    protected function _construct(): void { $this->_init(RmaReason::class, RmaReasonResource::class); }
}
