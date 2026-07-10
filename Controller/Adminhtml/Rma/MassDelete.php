<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_delete';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted    = 0;
        $errors     = 0;

        foreach ($collection as $rma) {
            try {
                $this->rmaRepository->deleteById((int) $rma->getRmaId());
                $deleted++;
            } catch (\Exception $e) {
                $errors++;
                $this->logger->warning('MassDelete failed for RMA ID ' . (int) $rma->getRmaId() . ': ' . $e->getMessage());
            }
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(__('%1 RMA(s) deleted successfully.', $deleted));
        }
        if ($errors > 0) {
            $this->messageManager->addErrorMessage(__('%1 RMA(s) could not be deleted.', $errors));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }
}
