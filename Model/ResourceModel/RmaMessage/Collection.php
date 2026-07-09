<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaMessage;

use Kkkonrad\Rma\Model\RmaMessage;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage as RmaMessageResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'message_id';
    protected function _construct(): void { $this->_init(RmaMessage::class, RmaMessageResource::class); }
}
