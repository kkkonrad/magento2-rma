<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RmaStatusHistory extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('kkkonrad_rma_status_history', 'history_id');
    }
}
