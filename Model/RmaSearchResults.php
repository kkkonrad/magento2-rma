<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class RmaSearchResults extends SearchResults implements RmaSearchResultsInterface
{
}
