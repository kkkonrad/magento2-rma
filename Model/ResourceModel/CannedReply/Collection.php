<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\CannedReply;

use Kkkonrad\Rma\Model\CannedReply;
use Kkkonrad\Rma\Model\ResourceModel\CannedReply as CannedReplyResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'reply_id';

    protected function _construct(): void
    {
        $this->_init(CannedReply::class, CannedReplyResource::class);
    }
}
