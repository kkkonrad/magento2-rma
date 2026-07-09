<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Customer\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory as ItemCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class PrintSlip extends Template
{
    private ?RmaInterface $rma = null;

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly CustomerSession $customerSession,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRma(): ?RmaInterface
    {
        if ($this->rma === null) {
            $rmaId      = (int) $this->getRequest()->getParam('rma_id');
            $customerId = (int) $this->customerSession->getCustomerId();

            if ($rmaId && $customerId) {
                try {
                    $rma = $this->rmaRepository->getById($rmaId);
                    if ((int) $rma->getCustomerId() === $customerId) {
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
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }
        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId());
        return $collection->getItems();
    }

    public function getReasons(): array
    {
        $collection = $this->reasonCollectionFactory->create();
        $reasons = [];
        foreach ($collection as $reason) {
            $reasons[$reason->getReasonId()] = $reason->getLabel();
        }
        return $reasons;
    }

    public function getConditions(): array
    {
        $collection = $this->conditionCollectionFactory->create();
        $conditions = [];
        foreach ($collection as $condition) {
            $conditions[$condition->getConditionId()] = $condition->getLabel();
        }
        return $conditions;
    }
}
