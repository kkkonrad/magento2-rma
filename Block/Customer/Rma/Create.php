<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Customer\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\Source\ResolutionType;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Create extends Template
{
    protected $_template = 'Kkkonrad_Rma::customer/rma/create.phtml';

    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        private readonly ResolutionType $resolutionTypeSource,
        private readonly Config $config,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEligibleOrders(): array
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->addFilter('status', 'complete')
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria);
        $eligible = [];

        foreach ($orders->getItems() as $order) {
            if ($this->rmaManagement->isOrderEligibleForRma((int) $order->getEntityId(), $customerId)) {
                $eligible[] = $order;
            }
        }

        return $eligible;
    }

    public function getReasons(): array
    {
        $collection = $this->reasonCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1])
            ->setOrder('sort_order', 'ASC');

        $options = [];
        foreach ($collection as $reason) {
            $options[] = [
                'value' => $reason->getReasonId(),
                'label' => $reason->getLabel(),
            ];
        }
        return $options;
    }

    public function getConditions(): array
    {
        $collection = $this->conditionCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1])
            ->setOrder('sort_order', 'ASC');

        $options = [];
        foreach ($collection as $condition) {
            $options[] = [
                'value' => $condition->getConditionId(),
                'label' => $condition->getLabel(),
            ];
        }
        return $options;
    }

    public function getResolutionTypes(): array
    {
        return $this->resolutionTypeSource->toOptionArray();
    }

    public function getMaxFileSizeMb(): int
    {
        return $this->config->getMaxFileSizeMb();
    }

    public function getAllowedExtensions(): string
    {
        return implode(',', $this->config->getAllowedExtensions());
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('rma/index/save');
    }

    public function getUploadUrl(): string
    {
        return $this->getUrl('rma/index/upload');
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
