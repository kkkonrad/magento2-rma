<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Resolution;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::resolutions_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->addBreadcrumb(__('RMA'), __('RMA'));
        $resultPage->addBreadcrumb(__('Manage Return Resolutions'), __('Manage Return Resolutions'));
        $resultPage->getConfig()->getTitle()->prepend(__('Return Resolutions'));

        return $resultPage;
    }
}
