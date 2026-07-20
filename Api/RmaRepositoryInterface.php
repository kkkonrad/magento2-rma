<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * RMA Repository Interface
 * @api
 */
interface RmaRepositoryInterface
{
    /**
     * Save RMA
     *
     * @param \Kkkonrad\Rma\Api\Data\RmaInterface $rma
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws CouldNotSaveException
     */
    public function save(RmaInterface $rma): RmaInterface;

    /**
     * Get RMA by ID
     *
     * @param int $rmaId
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $rmaId): RmaInterface;

    /**
     * Return an RMA only when it belongs to the supplied customer.
     *
     * @param int $rmaId
     * @param int $customerId
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws NoSuchEntityException
     */
    public function getByIdForCustomer(int $rmaId, int $customerId): RmaInterface;

    /**
     * Get RMA by increment ID
     *
     * @param string $incrementId
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     * @throws NoSuchEntityException
     */
    public function getByIncrementId(string $incrementId): RmaInterface;

    /**
     * Get list of RMAs
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RmaSearchResultsInterface;

    /**
     * Get list of RMAs for a specific customer (security-enforced)
     *
     * @param int $customerId
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface
     */
    public function getListForCustomer(
        int $customerId,
        ?SearchCriteriaInterface $searchCriteria = null
    ): RmaSearchResultsInterface;

    /**
     * Delete RMA
     *
     * @param \Kkkonrad\Rma\Api\Data\RmaInterface $rma
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(RmaInterface $rma): bool;

    /**
     * Delete RMA by ID
     *
     * @param int $rmaId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $rmaId): bool;
}
