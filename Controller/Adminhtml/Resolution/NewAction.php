<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Resolution;

use Magento\Backend\App\Action;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::resolutions_manage';

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        return $this->resultRedirectFactory->create()->setPath('*/*/edit');
    }
}
