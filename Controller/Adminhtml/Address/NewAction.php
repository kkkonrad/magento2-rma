<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Address;

use Magento\Backend\App\Action;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::addresses_manage';

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit');
    }
}
