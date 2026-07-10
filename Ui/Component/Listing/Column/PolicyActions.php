<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PolicyActions extends Column
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
                        'href'  => $this->urlBuilder->getUrl('kkkonrad_rma/policy/edit', ['policy_id' => $item['policy_id']]),
                        'label' => __('Edit'),
                    ],
                    'delete' => [
                        'href'    => $this->urlBuilder->getUrl('kkkonrad_rma/policy/delete', ['policy_id' => $item['policy_id']]),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title'   => __('Delete Policy'),
                            'message' => __('Are you sure you want to delete policy "%1"?', $item['name']),
                        ],
                    ],
                ];
            }
        }
        return $dataSource;
    }
}
