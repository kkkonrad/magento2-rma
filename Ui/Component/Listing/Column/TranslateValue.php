<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class TranslateValue extends Column
{
    /**
     * Translate the column value dynamically
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = __($item[$fieldName]);
                }
            }
        }
        return $dataSource;
    }
}
