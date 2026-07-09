<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Api\Data\RmaInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class Create extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_create';

    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaItemInterfaceFactory $rmaItemFactory,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = (int)$this->getRequest()->getParam('order_id');

        if (!$orderId) {
            $this->messageManager->addNoticeMessage(
                __('To create a return request, please view a completed order in Sales > Orders and click "Create RMA".')
            );
            return $resultRedirect->setPath('sales/order/index');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            
            if ($order->getStatus() !== 'complete') {
                throw new LocalizedException(__('Only completed orders can be returned.'));
            }

            // Get first active reason and condition
            $reasonCollection = $this->reasonCollectionFactory->create();
            $reasonCollection->addFieldToFilter('is_active', 1)->setOrder('sort_order', 'ASC');
            $reason = $reasonCollection->getFirstItem();
            $reasonId = $reason ? (int)$reason->getId() : null;

            $conditionCollection = $this->conditionCollectionFactory->create();
            $conditionCollection->addFieldToFilter('is_active', 1)->setOrder('sort_order', 'ASC');
            $condition = $conditionCollection->getFirstItem();
            $conditionId = $condition ? (int)$condition->getId() : null;

            // Prepare items
            $rmaItems = [];
            foreach ($order->getItems() as $orderItem) {
                if ($orderItem->isDummy()) {
                    continue;
                }
                
                $qtyOrdered = (float)$orderItem->getQtyOrdered();
                if ($qtyOrdered <= 0) {
                    continue;
                }

                /** @var \Kkkonrad\Rma\Api\Data\RmaItemInterface $rmaItem */
                $rmaItem = $this->rmaItemFactory->create();
                $rmaItem->setOrderItemId((int)$orderItem->getItemId())
                    ->setQty($qtyOrdered)
                    ->setReasonId($reasonId)
                    ->setConditionId($conditionId);
                
                $rmaItems[] = $rmaItem;
            }

            if (empty($rmaItems)) {
                throw new LocalizedException(__('This order has no items that can be returned.'));
            }

            // Create RMA via management contract
            $rma = $this->rmaManagement->createFromOrder(
                $orderId,
                (int)$order->getCustomerId(),
                RmaInterface::RESOLUTION_REFUND,
                $rmaItems,
                __('Created by admin from backend.')
            );

            // Auto-move to pending_review for admin
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                __('RMA initiated by store administrator.'),
                'admin',
                (int)$this->_auth->getUser()->getId()
            );

            $this->messageManager->addSuccessMessage(__('RMA has been successfully initiated.'));
            return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rma->getRmaId()]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while creating the RMA.'));
        }

        return $resultRedirect->setPath('kkkonrad_rma/rma/index');
    }
}
