<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\CannedReply;

use Kkkonrad\Rma\Model\CannedReplyFactory;
use Kkkonrad\Rma\Model\ResourceModel\CannedReply as CannedReplyResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::cannedreplies_manage';

    public function __construct(
        Context $context,
        private readonly CannedReplyFactory $cannedReplyFactory,
        private readonly CannedReplyResource $cannedReplyResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id    = (int) $this->getRequest()->getParam('reply_id');
            $model = $this->cannedReplyFactory->create();

            if ($id) {
                $this->cannedReplyResource->load($model, $id);
                if (!$model->getReplyId()) {
                    $this->messageManager->addErrorMessage(__('This canned reply no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            } else {
                unset($data['reply_id']);
            }

            $model->setData($data);

            try {
                $this->cannedReplyResource->save($model);
                $this->dataPersistor->clear('kkkonrad_rma_canned_reply');
                $this->messageManager->addSuccessMessage(__('You saved the canned reply.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['reply_id' => $model->getReplyId()]);
                }
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the canned reply.'));
            }

            $this->dataPersistor->set('kkkonrad_rma_canned_reply', $data);
            return $resultRedirect->setPath('*/*/edit', ['reply_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
