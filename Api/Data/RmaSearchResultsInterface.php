<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for RMA requests.
 *
 * @api
 */
interface RmaSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface[]
     */
    public function getItems();

    /**
     * @param \Kkkonrad\Rma\Api\Data\RmaInterface[] $items
     * @return \Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface
     */
    public function setItems(array $items);
}
