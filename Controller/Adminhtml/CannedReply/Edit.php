<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\CannedReply;

use Kkkonrad\Rma\Model\CannedReplyFactory;
use Kkkonrad\Rma\Model\ResourceModel\CannedReply as CannedReplyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::cannedreplies_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CannedReplyFactory $cannedReplyFactory,
        private readonly CannedReplyResource $cannedReplyResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('reply_id');
        $model = $this->cannedReplyFactory->create();

        if ($id) {
            $this->cannedReplyResource->load($model, $id);
            if (!$model->getReplyId()) {
                $this->messageManager->addErrorMessage(__('This canned reply no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->registry->register('kkkonrad_rma_canned_reply', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kkkonrad_Rma::rma_menu');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getReplyId() ? __('Edit Canned Reply "%1"', $model->getTitle()) : __('New Canned Reply')
        );

        return $resultPage;
    }
}
