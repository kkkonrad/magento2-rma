<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\CannedReply;

use Kkkonrad\Rma\Model\CannedReplyFactory;
use Kkkonrad\Rma\Model\ResourceModel\CannedReply as CannedReplyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::cannedreplies_manage';

    public function __construct(
        Context $context,
        private readonly CannedReplyFactory $cannedReplyFactory,
        private readonly CannedReplyResource $cannedReplyResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('reply_id');

        if ($id) {
            try {
                $model = $this->cannedReplyFactory->create();
                $this->cannedReplyResource->load($model, $id);

                if ($model->getReplyId()) {
                    $this->cannedReplyResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have deleted the canned reply.'));
                } else {
                    $this->messageManager->addErrorMessage(__('This canned reply no longer exists.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['reply_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a canned reply to delete.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
