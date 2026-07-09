<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Customer\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ListRma extends Template
{
    protected $_template = 'Kkkonrad_Rma::customer/rma/list.phtml';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly CustomerSession $customerSession,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface[]
     */
    public function getRmaList(): array
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        $page = max(1, (int) $this->getRequest()->getParam('p', 1));

        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->setCurrentPage($page)
            ->setPageSize(10)
            ->create();

        $results = $this->rmaRepository->getListForCustomer($customerId, $searchCriteria);

        return $results->getItems();
    }

    public function getStatusLabel(string $status): string
    {
        return (string) __(Status::getLabel($status));
    }

    public function getCreateUrl(): string
    {
        return $this->getUrl('rma/index/create');
    }

    public function getViewUrl(int $rmaId): string
    {
        return $this->getUrl('rma/index/view', ['rma_id' => $rmaId]);
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'new'             => 'bg-blue-100 text-blue-800',
            'pending_review'  => 'bg-yellow-100 text-yellow-800',
            'approved'        => 'bg-green-100 text-green-800',
            'rejected'        => 'bg-red-100 text-red-800',
            'item_in_transit' => 'bg-indigo-100 text-indigo-800',
            'item_received'   => 'bg-purple-100 text-purple-800',
            'resolved'        => 'bg-emerald-100 text-emerald-800',
            'closed'          => 'bg-gray-100 text-gray-600',
            'cancelled'       => 'bg-gray-100 text-gray-500',
            default           => 'bg-gray-100 text-gray-600',
        };
    }
}
