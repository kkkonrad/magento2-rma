<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class RmaActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = [
                    'edit' => [
                        'href'  => $this->urlBuilder->getUrl('kkkonrad_rma/rma/edit', ['rma_id' => $item['rma_id']]),
                        'label' => __('View'),
                    ],
                    'delete' => [
                        'href'    => $this->urlBuilder->getUrl('kkkonrad_rma/rma/delete', ['rma_id' => $item['rma_id']]),
                        'label'   => __('Delete'),
                        'post'    => true,
                        'confirm' => [
                            'title'   => __('Delete RMA'),
                            'message' => __('Are you sure you want to delete RMA #%1?', $item['increment_id']),
                        ],
                    ],
                ];
            }
        }
        return $dataSource;
    }
}
