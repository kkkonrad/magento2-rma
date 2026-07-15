<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolver;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for customerRmas query — returns paginated list for the authenticated customer.
 */
class CustomerRmaList implements ResolverInterface
{
    public function __construct(
        private readonly GetCustomer $getCustomer,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly Status $statusSource
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

        $customer   = $this->getCustomer->execute($context);
        $customerId = (int) $customer->getId();

        $pageSize    = min(100, max(1, (int) ($args['pageSize'] ?? 10)));
        $currentPage = max(1, (int) ($args['currentPage'] ?? 1));

        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->setCurrentPage($currentPage)
            ->setPageSize($pageSize)
            ->create();

        $results = $this->rmaRepository->getListForCustomer($customerId, $searchCriteria);

        $items = [];
        foreach ($results->getItems() as $rma) {
            $items[] = [
                'rma_id'            => (int) $rma->getRmaId(),
                'increment_id'      => (string) $rma->getIncrementId(),
                'order_increment_id'=> (string) $rma->getOrderIncrementId(),
                'status'            => (string) $rma->getStatus(),
                'status_label'      => (string) $this->statusSource->getLabel($rma->getStatus()),
                'resolution_type'   => (string) $rma->getResolutionType(),
                'created_at'        => (string) $rma->getCreatedAt(),
                'updated_at'        => (string) $rma->getUpdatedAt(),
            ];
        }

        return [
            'items'       => $items,
            'total_count' => (int) $results->getTotalCount(),
            'page_info'   => [
                'page_size'    => $pageSize,
                'current_page' => $currentPage,
                'total_pages'  => $pageSize > 0 ? (int) ceil($results->getTotalCount() / $pageSize) : 1,
            ],
        ];
    }
}
