<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Cancel extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly RmaManagementInterface $rmaManagement
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $rmaId   = (int) $this->getRequest()->getParam('rma_id');
        $comment = (string) $this->getRequest()->getParam('comment', '');

        try {
            $this->rmaManagement->cancel($rmaId, $comment ?: null);
            $this->messageManager->addSuccessMessage(__('RMA has been cancelled.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while cancelling the RMA.'));
        }

        return $this->_redirect('*/*/edit', ['rma_id' => $rmaId]);
    }
}
