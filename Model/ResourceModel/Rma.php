<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Rma extends AbstractDb
{
    public const TABLE_NAME = 'kkkonrad_rma';
    public const ID_FIELD   = 'rma_id';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD);
    }
}
