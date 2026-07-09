<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class RmaReasons implements ResolverInterface
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

        $reasons = [];
        foreach ($collection as $reason) {
            $reasons[] = [
                'reason_id' => (int) $reason->getReasonId(),
                'label'     => (string) $reason->getLabel(),
                'code'      => (string) $reason->getCode(),
            ];
        }

        return $reasons;
    }
}
