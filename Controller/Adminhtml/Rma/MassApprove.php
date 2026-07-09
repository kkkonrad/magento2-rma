<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;

class MassApprove extends Action
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
        $approved   = 0;
        $errors     = 0;

        foreach ($collection as $rma) {
            try {
                $this->rmaManagement->approve((int) $rma->getRmaId());
                $approved++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        if ($approved > 0) {
            $this->messageManager->addSuccessMessage(__('Approved %1 RMA(s).', $approved));
        }
        if ($errors > 0) {
            $this->messageManager->addErrorMessage(__('%1 RMA(s) could not be approved (invalid status transition).', $errors));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
