<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Condition;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Redirect "Add New" to Edit controller without ID (creates a new record)
 */
class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::conditions_manage';

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        return $this->_redirect('*/*/edit');
    }
}
