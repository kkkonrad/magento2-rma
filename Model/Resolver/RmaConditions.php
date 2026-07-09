<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class RmaConditions implements ResolverInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', 'ASC');

        $conditions = [];
        foreach ($collection as $condition) {
            $conditions[] = [
                'condition_id' => (int) $condition->getConditionId(),
                'label'        => (string) $condition->getLabel(),
                'code'         => (string) $condition->getCode(),
            ];
        }

        return $conditions;
    }
}
