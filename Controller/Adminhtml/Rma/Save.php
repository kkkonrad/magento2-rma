<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin Save controller — persists editable fields on the RMA detail form
 * Currently supports updating the comment / resolution_type fields by admin.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rmaId = (int) $this->getRequest()->getParam('rma_id');

        if (!$rmaId) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $rma = $this->rmaRepository->getById($rmaId);

            // Only update fields that admin is allowed to touch directly
            $comment        = $this->getRequest()->getParam('comment');
            $resolutionType = $this->getRequest()->getParam('resolution_type');

            if ($comment !== null) {
                $rma->setComment($comment);
            }

            if ($resolutionType !== null) {
                // Fix R3-3: Validate against defined resolution type constants
                $allowedTypes = [
                    RmaInterface::RESOLUTION_REFUND,
                    RmaInterface::RESOLUTION_EXCHANGE,
                    RmaInterface::RESOLUTION_REPAIR,
                    RmaInterface::RESOLUTION_VOUCHER,
                ];
                if (!in_array($resolutionType, $allowedTypes, true)) {
                    throw new LocalizedException(__('Invalid resolution type: %1', $resolutionType));
                }
                $rma->setResolutionType($resolutionType);
            }

            $this->rmaRepository->save($rma);
            $this->messageManager->addSuccessMessage(__('RMA has been saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving the RMA.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
