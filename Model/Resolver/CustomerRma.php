<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage\Collection as MessageCollection;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage\CollectionFactory as MessageCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory as ItemCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory\CollectionFactory as HistoryCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment\CollectionFactory as AttachmentCollectionFactory;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolver for customerRma query — returns full RMA details for the authenticated customer.
 */
class CustomerRma implements ResolverInterface
{
    public function __construct(
        private readonly GetCustomer $getCustomer,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly MessageCollectionFactory $messageCollectionFactory,
        private readonly HistoryCollectionFactory $historyCollectionFactory,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        private readonly StoreManagerInterface $storeManager
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

        $rmaId = (int) ($args['rma_id'] ?? 0);
        if (!$rmaId) {
            throw new GraphQlInputException(__('rma_id is required.'));
        }

        $customer   = $this->getCustomer->execute($context);
        $customerId = (int) $customer->getId();

        try {
            $rma = $this->rmaRepository->getById($rmaId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('RMA with ID %1 not found.', $rmaId));
        }

        // Security: customer can only access their own RMAs
        if ((int) $rma->getCustomerId() !== $customerId) {
            throw new GraphQlNoSuchEntityException(__('RMA with ID %1 not found.', $rmaId));
        }

        $baseMediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        // Items
        $itemCollection = $this->itemCollectionFactory->create()
            ->addFieldToFilter('rma_id', ['eq' => $rmaId]);
        $items = [];
        foreach ($itemCollection as $item) {
            $items[] = [
                'rma_item_id'   => (int) $item->getRmaItemId(),
                'order_item_id' => (int) $item->getOrderItemId(),
                'qty'           => (float) $item->getQty(),
                'reason_id'     => $item->getReasonId() ? (int) $item->getReasonId() : null,
                'condition_id'  => $item->getConditionId() ? (int) $item->getConditionId() : null,
            ];
        }

        // Messages (hide internal ones from customer)
        $messageCollection = $this->messageCollectionFactory->create()
            ->addFieldToFilter('rma_id', ['eq' => $rmaId])
            ->addFieldToFilter('is_internal', ['eq' => 0])
            ->setOrder('created_at', 'ASC');
        $messages = [];
        foreach ($messageCollection as $msg) {
            $messages[] = [
                'message_id'  => (int) $msg->getMessageId(),
                'message'     => (string) $msg->getMessage(),
                'author_type' => (string) $msg->getAuthorType(),
                'author_name' => (string) $msg->getAuthorName(),
                'is_internal' => (bool) $msg->getIsInternal(),
                'created_at'  => (string) $msg->getCreatedAt(),
            ];
        }

        // Status history
        $historyCollection = $this->historyCollectionFactory->create()
            ->addFieldToFilter('rma_id', ['eq' => $rmaId])
            ->setOrder('created_at', 'ASC');
        $history = [];
        foreach ($historyCollection as $entry) {
            $history[] = [
                'history_id'  => (int) $entry->getHistoryId(),
                'status_from' => $entry->getStatusFrom() ? (string) $entry->getStatusFrom() : null,
                'status_to'   => (string) $entry->getStatusTo(),
                'comment'     => $entry->getComment() ? (string) $entry->getComment() : null,
                'changed_by'  => (string) $entry->getChangedBy(),
                'created_at'  => (string) $entry->getCreatedAt(),
            ];
        }

        // Attachments
        $attachmentCollection = $this->attachmentCollectionFactory->create()
            ->addFieldToFilter('rma_id', ['eq' => $rmaId]);
        $attachments = [];
        foreach ($attachmentCollection as $att) {
            $attachments[] = [
                'attachment_id' => (int) $att->getAttachmentId(),
                'file_name'     => (string) $att->getFileName(),
                'file_size'     => (int) $att->getFileSize(),
                'mime_type'     => (string) $att->getMimeType(),
                'url'           => $baseMediaUrl . $att->getFilePath(),
            ];
        }

        return [
            'rma_id'             => (int) $rma->getRmaId(),
            'increment_id'       => (string) $rma->getIncrementId(),
            'order_id'           => (int) $rma->getOrderId(),
            'order_increment_id' => (string) $rma->getOrderIncrementId(),
            'status'             => (string) $rma->getStatus(),
            'status_label'       => (string) __(Status::getLabel($rma->getStatus())),
            'resolution_type'    => (string) $rma->getResolutionType(),
            'comment'            => $rma->getComment() ? (string) $rma->getComment() : null,
            'created_at'         => (string) $rma->getCreatedAt(),
            'updated_at'         => (string) $rma->getUpdatedAt(),
            'items'              => $items,
            'messages'           => $messages,
            'status_history'     => $history,
            'attachments'        => $attachments,
        ];
    }
}
