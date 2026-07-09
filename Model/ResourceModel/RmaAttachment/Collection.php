<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel\RmaAttachment;

use Kkkonrad\Rma\Model\RmaAttachment;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as RmaAttachmentResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'attachment_id';
    protected function _construct(): void { $this->_init(RmaAttachment::class, RmaAttachmentResource::class); }
}
