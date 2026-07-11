<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class SaveAdminRma extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_create';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaItemInterfaceFactory $rmaItemFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $orderId        = (int) $this->getRequest()->getPost('order_id');
            $resolutionType = (string) $this->getRequest()->getPost('resolution_type');
            $comment        = (string) $this->getRequest()->getPost('comment');
            $itemsJson      = (string) $this->getRequest()->getPost('items', '[]');
            $itemsData      = json_decode($itemsJson, true) ?? [];

            if (!$orderId || !$resolutionType || empty($itemsData)) {
                throw new LocalizedException(__('Please fill in all required fields.'));
            }

            $order = $this->orderRepository->get($orderId);

            // Build RmaItem objects
            $items = [];
            foreach ($itemsData as $itemData) {
                /** @var \Kkkonrad\Rma\Api\Data\RmaItemInterface $item */
                $item = $this->rmaItemFactory->create();
                $item->setOrderItemId((int)($itemData['order_item_id'] ?? 0))
                    ->setQty((float)($itemData['qty'] ?? 1))
                    ->setReasonId(isset($itemData['reason_id']) && $itemData['reason_id'] !== ''
                        ? (int)$itemData['reason_id'] : null)
                    ->setConditionId(isset($itemData['condition_id']) && $itemData['condition_id'] !== ''
                        ? (int)$itemData['condition_id'] : null);
                $items[] = $item;
            }

            // Create RMA
            $rma = $this->rmaManagement->createFromOrder(
                $orderId,
                (int)$order->getCustomerId(),
                $resolutionType,
                $items,
                $comment,
                true
            );

            // Auto-move to pending_review
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                (string)__('RMA initiated by store administrator.'),
                'admin',
                (int)$this->_auth->getUser()->getId()
            );

            return $result->setData([
                'success'      => true,
                'rma_id'       => $rma->getRmaId(),
                'redirect_url' => $this->getUrl('kkkonrad_rma/rma/edit', ['rma_id' => $rma->getRmaId()]),
                'message'      => (string) __('The return request has been submitted successfully.'),
            ]);

        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => (string) __('An error occurred. Please try again.')]);
        }
    }
}
