<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaMessageInterface;

/**
 * Customer-facing API. The authenticated customer is derived from UserContext,
 * never accepted from an API request.
 *
 * @api
 */
interface CustomerRmaManagementInterface
{
    /**
     * @param int $orderId
     * @param string $resolutionType
     * @param \Kkkonrad\Rma\Api\Data\RmaItemInterface[] $items
     * @param string|null $comment
     * @param bool $termsAccepted
     * @return \Kkkonrad\Rma\Api\Data\RmaInterface
     */
    public function createFromOrder(
        int $orderId,
        string $resolutionType,
        array $items,
        ?string $comment = null,
        bool $termsAccepted = false
    ): RmaInterface;

    /**
     * @param int $rmaId
     * @param string $message
     * @return \Kkkonrad\Rma\Api\Data\RmaMessageInterface
     */
    public function addMessage(int $rmaId, string $message): RmaMessageInterface;
}
