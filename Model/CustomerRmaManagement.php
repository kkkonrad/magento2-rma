<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\CustomerRmaManagementInterface;
use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AuthorizationException;

class CustomerRmaManagement implements CustomerRmaManagementInterface
{
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
    }

    public function createFromOrder(int $orderId, string $resolutionType, array $items, ?string $comment = null, bool $termsAccepted = false): RmaInterface
    {
        $customerId = $this->getCustomerId();
        $rma = null;
        try {
            $rma = $this->rmaManagement->createFromOrder(
                $orderId,
                $customerId,
                $resolutionType,
                $items,
                $comment,
                $termsAccepted,
                false,
                false
            );

            $rma = $this->rmaManagement->changeStatus(
                (int) $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                null,
                'customer',
                $customerId
            );
            $this->eventManager->dispatch('kkkonrad_rma_created', ['rma' => $rma, 'items' => $items]);
            return $rma;
        } catch (\Throwable $exception) {
            if ($rma !== null && $rma->getRmaId()) {
                try {
                    $this->rmaRepository->deleteById((int) $rma->getRmaId());
                } catch (\Throwable) {
                    // Preserve the original API failure.
                }
            }
            throw $exception;
        }
    }

    public function addMessage(int $rmaId, string $message): RmaMessageInterface
    {
        $customerId = $this->getCustomerId();
        $this->rmaRepository->getByIdForCustomer($rmaId, $customerId);

        return $this->rmaManagement->addMessage(
            $rmaId,
            $message,
            RmaMessageInterface::AUTHOR_CUSTOMER,
            $customerId,
            null,
            false
        );
    }

    private function getCustomerId(): int
    {
        $customerId = (int) $this->userContext->getUserId();
        if ($this->userContext->getUserType() !== UserContextInterface::USER_TYPE_CUSTOMER || !$customerId) {
            throw new AuthorizationException(__('The current customer is not authorized.'));
        }
        return $customerId;
    }
}
