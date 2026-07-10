<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy\CollectionFactory;

class PolicyOptions extends AbstractSource
{
    public function __construct(
        private readonly CollectionFactory $policyCollectionFactory
    ) {
    }

    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('Use Global Return Window')]
            ];
            $collection = $this->policyCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            foreach ($collection as $policy) {
                $this->_options[] = [
                    'value' => $policy->getId(),
                    'label' => $policy->getName()
                ];
            }
        }
        return $this->_options;
    }
}
