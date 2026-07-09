<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaItemInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Magento\Framework\Model\AbstractModel;

class RmaItem extends AbstractModel implements RmaItemInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_item';

    protected function _construct(): void
    {
        $this->_init(RmaItemResource::class);
    }

    public function getItemId(): ?int { $v = $this->getData(self::ITEM_ID); return $v !== null ? (int)$v : null; }
    public function setItemId(int $itemId): self { return $this->setData(self::ITEM_ID, $itemId); }
    public function getRmaId(): int { return (int)$this->getData(self::RMA_ID); }
    public function setRmaId(int $rmaId): self { return $this->setData(self::RMA_ID, $rmaId); }
    public function getOrderItemId(): int { return (int)$this->getData(self::ORDER_ITEM_ID); }
    public function setOrderItemId(int $orderItemId): self { return $this->setData(self::ORDER_ITEM_ID, $orderItemId); }
    public function getQty(): float { return (float)$this->getData(self::QTY); }
    public function setQty(float $qty): self { return $this->setData(self::QTY, $qty); }
    public function getReasonId(): ?int { $v = $this->getData(self::REASON_ID); return $v !== null ? (int)$v : null; }
    public function setReasonId(?int $reasonId): self { return $this->setData(self::REASON_ID, $reasonId); }
    public function getConditionId(): ?int { $v = $this->getData(self::CONDITION_ID); return $v !== null ? (int)$v : null; }
    public function setConditionId(?int $conditionId): self { return $this->setData(self::CONDITION_ID, $conditionId); }
    public function getUnitPrice(): ?float { $v = $this->getData(self::UNIT_PRICE); return $v !== null ? (float)$v : null; }
    public function setUnitPrice(?float $unitPrice): self { return $this->setData(self::UNIT_PRICE, $unitPrice); }
    public function getProductName(): ?string { return $this->getData(self::PRODUCT_NAME); }
    public function setProductName(?string $productName): self { return $this->setData(self::PRODUCT_NAME, $productName); }
    public function getProductSku(): ?string { return $this->getData(self::PRODUCT_SKU); }
    public function setProductSku(?string $productSku): self { return $this->setData(self::PRODUCT_SKU, $productSku); }
}
