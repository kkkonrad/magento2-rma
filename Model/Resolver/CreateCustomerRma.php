<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CreateCustomerRma implements ResolverInterface
{
    public function __construct(
        private readonly GetCustomer $getCustomer,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaItemInterfaceFactory $rmaItemFactory,
        private readonly CustomerRma $customerRmaResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        if (!$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer is not authorized.'));
        }

        $input = $args['input'] ?? [];
        $orderId = (int) ($input['order_id'] ?? 0);
        $resolutionType = (string) ($input['resolution_type'] ?? '');
        $comment = (string) ($input['comment'] ?? '');
        $termsAccepted = (bool) ($input['terms_accepted'] ?? false);
        $itemsData = $input['items'] ?? [];

        if (!$orderId) {
            throw new GraphQlInputException(__('order_id is required.'));
        }
        if (!$resolutionType) {
            throw new GraphQlInputException(__('resolution_type is required.'));
        }
        if (empty($itemsData)) {
            throw new GraphQlInputException(__('items list cannot be empty.'));
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = (int) $customer->getId();

        // Validate order belongs to customer
        try {
            $order = $this->orderRepository->get($orderId);
            if ((int) $order->getCustomerId() !== $customerId) {
                throw new GraphQlInputException(__('Order not found or access denied.'));
            }
        } catch (NoSuchEntityException) {
            throw new GraphQlInputException(__('Order with ID %1 not found.', $orderId));
        }

        // Build RMA Items
        $items = [];
        foreach ($itemsData as $itemData) {
            $orderItemId = (int) ($itemData['order_item_id'] ?? 0);
            $qty = (float) ($itemData['qty'] ?? 0);
            $reasonId = isset($itemData['reason_id']) ? (int) $itemData['reason_id'] : null;
            $conditionId = isset($itemData['condition_id']) ? (int) $itemData['condition_id'] : null;

            if (!$orderItemId) {
                throw new GraphQlInputException(__('order_item_id is required for each item.'));
            }
            if ($qty <= 0) {
                throw new GraphQlInputException(__('qty must be positive.'));
            }

            /** @var \Kkkonrad\Rma\Api\Data\RmaItemInterface $item */
            $item = $this->rmaItemFactory->create();
            $item->setOrderItemId($orderItemId)
                ->setQty($qty)
                ->setReasonId($reasonId)
                ->setConditionId($conditionId);

            $items[] = $item;
        }

        // Create RMA
        try {
            $rma = $this->rmaManagement->createFromOrder($orderId, $customerId, $resolutionType, $items, $comment, $termsAccepted);

            // Auto-advance to pending_review
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                null,
                'customer',
                $customerId
            );
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        // Return details
        $rmaData = $this->customerRmaResolver->resolve(
            $field,
            $context,
            $info,
            null,
            ['rma_id' => (int) $rma->getRmaId()]
        );

        return ['rma' => $rmaData];
    }
}
