<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Item Interface
 * @api
 */
interface RmaItemInterface
{
    public const ITEM_ID       = 'item_id';
    public const RMA_ID        = 'rma_id';
    public const ORDER_ITEM_ID = 'order_item_id';
    public const QTY           = 'qty';
    public const REASON_ID     = 'reason_id';
    public const CONDITION_ID  = 'condition_id';
    public const UNIT_PRICE    = 'unit_price';
    public const PRODUCT_NAME  = 'product_name';
    public const PRODUCT_SKU   = 'product_sku';

    public function getItemId(): ?int;
    public function setItemId(int $itemId): self;

    public function getRmaId(): int;
    public function setRmaId(int $rmaId): self;

    public function getOrderItemId(): int;
    public function setOrderItemId(int $orderItemId): self;

    public function getQty(): float;
    public function setQty(float $qty): self;

    public function getReasonId(): ?int;
    public function setReasonId(?int $reasonId): self;

    public function getConditionId(): ?int;
    public function setConditionId(?int $conditionId): self;

    public function getUnitPrice(): ?float;
    public function setUnitPrice(?float $unitPrice): self;

    public function getProductName(): ?string;
    public function setProductName(?string $productName): self;

    public function getProductSku(): ?string;
    public function setProductSku(?string $productSku): self;
}
