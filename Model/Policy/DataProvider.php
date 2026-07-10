<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\Policy;

use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy\CollectionFactory;
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

        /** @var \Kkkonrad\Rma\Model\RmaPolicy|null $policy */
        $policy = $this->registry->registry('kkkonrad_rma_policy');

        if ($policy) {
            $id = $policy->getId();
            $this->loadedData[$id ?: ''] = $policy->getData();
        } else {
            foreach ($this->collection->getItems() as $item) {
                $this->loadedData[$item->getId()] = $item->getData();
            }
        }

        $persistedData = $this->dataPersistor->get('kkkonrad_rma_policy');
        if (!empty($persistedData)) {
            $persistedId = $persistedData['policy_id'] ?? null;
            $this->loadedData[$persistedId ?: ''] = $persistedData;
            $this->dataPersistor->clear('kkkonrad_rma_policy');
        }

        return $this->loadedData;
    }
}
