<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;

class AddCustomerRmaMessage implements ResolverInterface
{
    public function __construct(
        private readonly GetCustomer $getCustomer,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement
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
        $rmaId = (int) ($input['rma_id'] ?? 0);
        $messageText = trim((string) ($input['message'] ?? ''));

        if (!$rmaId) {
            throw new GraphQlInputException(__('rma_id is required.'));
        }
        if (!$messageText) {
            throw new GraphQlInputException(__('message cannot be empty.'));
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = (int) $customer->getId();
        $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());

        try {
            $rma = $this->rmaRepository->getById($rmaId);
            if ((int) $rma->getCustomerId() !== $customerId) {
                throw new GraphQlInputException(__('RMA not found or access denied.'));
            }
        } catch (NoSuchEntityException) {
            throw new GraphQlInputException(__('RMA with ID %1 not found.', $rmaId));
        }

        try {
            $createdMsg = $this->rmaManagement->addMessage(
                $rmaId,
                $messageText,
                RmaMessageInterface::AUTHOR_CUSTOMER,
                $customerId,
                $customerName,
                false
            );
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return [
            'message_id'  => (int) $createdMsg->getMessageId(),
            'message'     => (string) $createdMsg->getMessage(),
            'author_type' => (string) $createdMsg->getAuthorType(),
            'author_name' => (string) $createdMsg->getAuthorName(),
            'is_internal' => (bool) $createdMsg->getIsInternal(),
            'created_at'  => (string) $createdMsg->getCreatedAt(),
        ];
    }
}
