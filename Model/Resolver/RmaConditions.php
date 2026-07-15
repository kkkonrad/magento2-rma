<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Model\DictionaryLabelTranslator;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class RmaConditions implements ResolverInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly DictionaryLabelTranslator $dictionaryLabelTranslator
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
                'label'        => (string) $this->dictionaryLabelTranslator->getConditionLabel(
                    (string) $condition->getCode(),
                    (string) $condition->getLabel()
                ),
                'code'         => (string) $condition->getCode(),
            ];
        }

        return $conditions;
    }
}
