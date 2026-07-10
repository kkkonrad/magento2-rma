<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Adminhtml\Rma;

use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\Source\ResolutionType;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Create extends Template
{
    public function __construct(
        Context $context,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        private readonly ResolutionType $resolutionTypeSource,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
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

    public function getSaveUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/saveAdminRma');
    }

    public function getSearchOrderUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/searchOrder');
    }
}
