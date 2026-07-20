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

    /** @return int|null */
    public function getItemId(): ?int;
    /** @return $this */
    public function setItemId(int $itemId): self;

    /** @return int */
    public function getRmaId(): int;
    /** @return $this */
    public function setRmaId(int $rmaId): self;

    /** @return int */
    public function getOrderItemId(): int;
    /** @return $this */
    public function setOrderItemId(int $orderItemId): self;

    /** @return float */
    public function getQty(): float;
    /** @return $this */
    public function setQty(float $qty): self;

    /** @return int|null */
    public function getReasonId(): ?int;
    /** @return $this */
    public function setReasonId(?int $reasonId): self;

    /** @return int|null */
    public function getConditionId(): ?int;
    /** @return $this */
    public function setConditionId(?int $conditionId): self;

    /** @return float|null */
    public function getUnitPrice(): ?float;
    /** @return $this */
    public function setUnitPrice(?float $unitPrice): self;

    /** @return string|null */
    public function getProductName(): ?string;
    /** @return $this */
    public function setProductName(?string $productName): self;

    /** @return string|null */
    public function getProductSku(): ?string;
    /** @return $this */
    public function setProductSku(?string $productSku): self;
}
