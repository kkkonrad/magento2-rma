<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Customer\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory as ItemCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Model\DictionaryLabelTranslator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

use Kkkonrad\Rma\Api\Data\RmaAddressInterface;
use Kkkonrad\Rma\Model\RmaAddressFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as RmaAddressResource;

class PrintSlip extends Template
{
    private ?RmaInterface $rma = null;
    private ?array $items = null;
    private ?array $reasons = null;
    private ?array $conditions = null;

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly CustomerSession $customerSession,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        private readonly RmaAddressFactory $addressFactory,
        private readonly RmaAddressResource $addressResource,
        private readonly DictionaryLabelTranslator $dictionaryLabelTranslator,
        private readonly \Kkkonrad\Rma\Model\GuestAccessToken $guestAccessToken,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }


    public function getRma(): ?RmaInterface
    {
        if ($this->rma === null) {
            $rmaId      = (int) $this->getRequest()->getParam('rma_id');
            $customerId = (int) $this->customerSession->getCustomerId();

            if ($rmaId) {
                try {
                    $rma = $this->rmaRepository->getById($rmaId);
                    $guestToken = (string) $this->getRequest()->getParam('hash');
                    $isCustomerOwner = $customerId > 0 && (int) $rma->getCustomerId() === $customerId;
                    $isAuthorizedGuest = (int) $rma->getCustomerId() === 0
                        && $this->guestAccessToken->isValid($rma, $guestToken);
                    if ($isCustomerOwner || $isAuthorizedGuest) {
                        $this->rma = $rma;
                    }
                } catch (NoSuchEntityException) {
                    $this->rma = null;
                }
            }
        }
        return $this->rma;
    }

    public function getItems(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $rma = $this->getRma();
        if (!$rma) {
            return $this->items = [];
        }
        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId());
        return $this->items = array_values($collection->getItems());
    }

    public function getReasons(): array
    {
        if ($this->reasons !== null) {
            return $this->reasons;
        }

        $collection = $this->reasonCollectionFactory->create();
        // Fix R3-4: Only show active reasons in print slip
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $result = [];
        foreach ($collection as $reason) {
            $result[$reason->getReasonId()] = (string) $this->dictionaryLabelTranslator->getReasonLabel(
                (string) $reason->getCode(),
                (string) $reason->getLabel()
            );
        }
        return $this->reasons = $result;
    }

    public function getConditions(): array
    {
        if ($this->conditions !== null) {
            return $this->conditions;
        }

        $collection = $this->conditionCollectionFactory->create();
        // Fix R3-4: Only show active conditions in print slip
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $result = [];
        foreach ($collection as $condition) {
            $result[$condition->getConditionId()] = (string) $this->dictionaryLabelTranslator->getConditionLabel(
                (string) $condition->getCode(),
                (string) $condition->getLabel()
            );
        }
        return $this->conditions = $result;
    }

    public function getResolutionLabel(string $resolutionType): string
    {
        if ($resolutionType === '') {
            return '-';
        }

        return (string) $this->dictionaryLabelTranslator->getResolutionLabel(
            $resolutionType,
            ucfirst(str_replace('_', ' ', $resolutionType))
        );
    }

    public function getReturnAddress(): ?RmaAddressInterface
    {
        $rma = $this->getRma();
        if (!$rma || !$rma->getReturnAddressId()) {
            return null;
        }
        $address = $this->addressFactory->create();
        $this->addressResource->load($address, $rma->getReturnAddressId());
        return $address->getId() ? $address : null;
    }
}
