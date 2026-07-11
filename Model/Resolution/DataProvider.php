<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Resolution;

use Kkkonrad\Rma\Model\ResourceModel\RmaResolution\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
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

        /** @var \Kkkonrad\Rma\Model\RmaResolution|null $resolution */
        $resolution = $this->registry->registry('kkkonrad_rma_resolution');

        if ($resolution) {
            $id = $resolution->getId();
            $this->loadedData[$id ?: ''] = $resolution->getData();
        } else {
            foreach ($this->collection->getItems() as $item) {
                $this->loadedData[$item->getId()] = $item->getData();
            }
        }

        $persistedData = $this->dataPersistor->get('kkkonrad_rma_resolution');
        if (!empty($persistedData)) {
            $persistedId = $persistedData['resolution_id'] ?? null;
            $this->loadedData[$persistedId ?: ''] = $persistedData;
            $this->dataPersistor->clear('kkkonrad_rma_resolution');
        }

        return $this->loadedData;
    }
}
