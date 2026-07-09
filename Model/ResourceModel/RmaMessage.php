<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RmaMessage extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('kkkonrad_rma_message', 'message_id');
    }
}
