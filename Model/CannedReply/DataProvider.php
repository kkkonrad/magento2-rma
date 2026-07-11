<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model\CannedReply;

use Kkkonrad\Rma\Model\ResourceModel\CannedReply\CollectionFactory;
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

        /** @var \Kkkonrad\Rma\Model\CannedReply|null $reply */
        $reply = $this->registry->registry('kkkonrad_rma_canned_reply');

        if ($reply) {
            $id = $reply->getId();
            $this->loadedData[$id ?: ''] = $reply->getData();
        } else {
            foreach ($this->collection->getItems() as $item) {
                $this->loadedData[$item->getId()] = $item->getData();
            }
        }

        $persistedData = $this->dataPersistor->get('kkkonrad_rma_canned_reply');
        if (!empty($persistedData)) {
            $persistedId = $persistedData['reply_id'] ?? null;
            $this->loadedData[$persistedId ?: ''] = $persistedData;
            $this->dataPersistor->clear('kkkonrad_rma_canned_reply');
        }

        return $this->loadedData;
    }
}
