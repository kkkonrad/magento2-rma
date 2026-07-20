<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface;
use Kkkonrad\Rma\Api\Data\RmaSearchResultsInterfaceFactory;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\Rma as RmaResource;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class RmaRepository implements RmaRepositoryInterface
{
    public function __construct(
        private readonly RmaFactory $rmaFactory,
        private readonly RmaResource $rmaResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly RmaSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly AttachmentUploader $attachmentUploader,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(RmaInterface $rma): RmaInterface
    {
        try {
            $this->rmaResource->save($rma);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save RMA: %1', $e->getMessage()),
                $e
            );
        }
        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $rmaId): RmaInterface
    {
        /** @var Rma $rma */
        $rma = $this->rmaFactory->create();
        $this->rmaResource->load($rma, $rmaId);

        if (!$rma->getRmaId()) {
            throw new NoSuchEntityException(__('RMA with ID "%1" does not exist.', $rmaId));
        }

        return $rma;
    }

    public function getByIdForCustomer(int $rmaId, int $customerId): RmaInterface
    {
        $rma = $this->getById($rmaId);
        if ((int) $rma->getCustomerId() !== $customerId) {
            throw new NoSuchEntityException(__('RMA with ID "%1" does not exist.', $rmaId));
        }
        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function getByIncrementId(string $incrementId): RmaInterface
    {
        /** @var Rma $rma */
        $rma = $this->rmaFactory->create();
        $this->rmaResource->load($rma, $incrementId, 'increment_id');

        if (!$rma->getRmaId()) {
            throw new NoSuchEntityException(
                __('RMA with increment ID "%1" does not exist.', $incrementId)
            );
        }

        return $rma;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RmaSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     *
     * Security: enforces customer_id filter so customers can only see their own RMAs.
     */
    public function getListForCustomer(
        int $customerId,
        ?SearchCriteriaInterface $searchCriteria = null
    ): RmaSearchResultsInterface {
        $searchCriteria ??= $this->searchCriteriaBuilder->create();
        $collection = $this->collectionFactory->create();
        // Process caller-provided criteria first (sorting, pagination)
        $this->collectionProcessor->process($searchCriteria, $collection);
        // Fix R3: Security filter applied AFTER collectionProcessor so it cannot be overridden
        // by any customer_id filter injected via SearchCriteria from GraphQL/API
        $collection->addFieldToFilter('customer_id', ['eq' => $customerId]);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(RmaInterface $rma): bool
    {
        $rmaId = (int) $rma->getRmaId();
        $shippingLabel = $rma->getShippingLabel() ? (string) $rma->getShippingLabel() : null;
        try {
            $this->rmaResource->delete($rma);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete RMA with ID %1: %2', $rma->getRmaId(), $e->getMessage()),
                $e
            );
        }

        try {
            $this->attachmentUploader->deleteForRma($rmaId, $shippingLabel);
        } catch (\Throwable $exception) {
            $this->logger->warning('RMA files could not be removed after entity deletion.', [
                'rma_id' => $rmaId,
                'exception' => $exception,
            ]);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $rmaId): bool
    {
        return $this->delete($this->getById($rmaId));
    }
}
