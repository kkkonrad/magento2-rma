<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Reason;

use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * UI Form DataProvider for Return Reason.
 *
 * Uses the Registry (populated by Edit controller) to load the current
 * record during the initial page request. DataPersistor restores data
 * after a failed save.
 */
class DataProvider extends AbstractDataProvider
{
    protected $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        // Primary path: read from registry (set by Edit controller in same request)
        /** @var \Kkkonrad\Rma\Model\RmaReason|null $reason */
        $reason = $this->registry->registry('kkkonrad_rma_reason');

        if ($reason) {
            $id = $reason->getId();
            $this->loadedData[$id ?: ''] = $reason->getData();
        } else {
            // Fallback: load all records (e.g., during mui/index/render AJAX)
            foreach ($this->collection->getItems() as $item) {
                $this->loadedData[$item->getId()] = $item->getData();
            }
        }

        // Restore data after a failed save
        $persistedData = $this->dataPersistor->get('kkkonrad_rma_reason');
        if (!empty($persistedData)) {
            $persistedId = $persistedData['reason_id'] ?? null;
            $this->loadedData[$persistedId ?: ''] = $persistedData;
            $this->dataPersistor->clear('kkkonrad_rma_reason');
        }

        return $this->loadedData;
    }
}
