<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\CannedReply;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::cannedreplies_manage';

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
        $resultPage->addBreadcrumb(__('Manage Canned Replies'), __('Manage Canned Replies'));
        $resultPage->getConfig()->getTitle()->prepend(__('Canned Replies'));

        return $resultPage;
    }
}
