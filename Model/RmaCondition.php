<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaConditionInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition as RmaConditionResource;
use Magento\Framework\Model\AbstractModel;

class RmaCondition extends AbstractModel implements RmaConditionInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_condition';

    protected $_idFieldName = 'condition_id';

    protected function _construct(): void { $this->_init(RmaConditionResource::class); }

    public function getConditionId(): ?int { $v = $this->getData(self::CONDITION_ID); return $v !== null ? (int)$v : null; }
    public function setConditionId(int $conditionId): self { return $this->setData(self::CONDITION_ID, $conditionId); }
    public function getLabel(): string { return (string)$this->getData(self::LABEL); }
    public function setLabel(string $label): self { return $this->setData(self::LABEL, $label); }
    public function getCode(): string { return (string)$this->getData(self::CODE); }
    public function setCode(string $code): self { return $this->setData(self::CODE, $code); }
    public function getSortOrder(): int { return (int)$this->getData(self::SORT_ORDER); }
    public function setSortOrder(int $sortOrder): self { return $this->setData(self::SORT_ORDER, $sortOrder); }
    public function getIsActive(): bool { return (bool)$this->getData(self::IS_ACTIVE); }
    public function setIsActive(bool $isActive): self { return $this->setData(self::IS_ACTIVE, $isActive); }
}
