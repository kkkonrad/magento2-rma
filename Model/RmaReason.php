<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaReasonInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason as RmaReasonResource;
use Magento\Framework\Model\AbstractModel;

class RmaReason extends AbstractModel implements RmaReasonInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_reason';

    protected function _construct(): void { $this->_init(RmaReasonResource::class); }

    public function getReasonId(): ?int { $v = $this->getData(self::REASON_ID); return $v !== null ? (int)$v : null; }
    public function setReasonId(int $reasonId): self { return $this->setData(self::REASON_ID, $reasonId); }
    public function getLabel(): string { return (string)$this->getData(self::LABEL); }
    public function setLabel(string $label): self { return $this->setData(self::LABEL, $label); }
    public function getCode(): string { return (string)$this->getData(self::CODE); }
    public function setCode(string $code): self { return $this->setData(self::CODE, $code); }
    public function getSortOrder(): int { return (int)$this->getData(self::SORT_ORDER); }
    public function setSortOrder(int $sortOrder): self { return $this->setData(self::SORT_ORDER, $sortOrder); }
    public function getIsActive(): bool { return (bool)$this->getData(self::IS_ACTIVE); }
    public function setIsActive(bool $isActive): self { return $this->setData(self::IS_ACTIVE, $isActive); }
}
