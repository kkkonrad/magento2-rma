<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Psr\Log\LoggerInterface;

class MassCancel extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $cancelled  = 0;
        $errors     = 0;

        foreach ($collection as $rma) {
            try {
                $this->rmaManagement->cancel(
                    (int) $rma->getRmaId(),
                    (string) __('Cancelled by administrator.'),
                    'admin',
                    (int) $this->_auth->getUser()->getId()
                );
                $cancelled++;
            } catch (\Exception $e) {
                $errors++;
                // Fix R3-6: Log exact failure reason for each failed RMA
                $this->logger->warning('MassCancel failed for RMA ID ' . (int) $rma->getRmaId() . ': ' . $e->getMessage());
            }
        }

        if ($cancelled > 0) {
            $this->messageManager->addSuccessMessage(__('Cancelled %1 RMA(s).', $cancelled));
        }
        if ($errors > 0) {
            $this->messageManager->addErrorMessage(__('%1 RMA(s) could not be cancelled (already in terminal status).', $errors));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
