<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;

class MassCancel extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RmaManagementInterface $rmaManagement
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
                    (string) __('Cancelled by administrator.')
                );
                $cancelled++;
            } catch (\Exception $e) {
                $errors++;
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
