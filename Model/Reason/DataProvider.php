<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Reason;

use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly RequestInterface $request,
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

        // Filter collection to only the requested record when editing
        $id = (int) $this->request->getParam($this->requestFieldName);
        if ($id) {
            $this->collection->addFieldToFilter($this->primaryFieldName, ['eq' => $id]);
        }

        foreach ($this->collection->getItems() as $item) {
            $itemId = $item->getData($this->primaryFieldName);
            $this->loadedData[$itemId] = $item->getData();
        }

        // Restore data after a failed save (validation error, redirect back)
        $persistedData = $this->dataPersistor->get('kkkonrad_rma_reason');
        if (!empty($persistedData)) {
            $persistedId = $persistedData['reason_id'] ?? null;
            $key = $persistedId ?: '';
            $this->loadedData[$key] = $persistedData;
            $this->dataPersistor->clear('kkkonrad_rma_reason');
        }

        return $this->loadedData;
    }
}
