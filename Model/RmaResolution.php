<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaResolutionInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaResolution as RmaResolutionResource;
use Magento\Framework\Model\AbstractModel;

class RmaResolution extends AbstractModel implements RmaResolutionInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_resolution';

    protected $_idFieldName = 'resolution_id';

    protected function _construct(): void
    {
        $this->_init(RmaResolutionResource::class);
    }

    public function getResolutionId(): ?int
    {
        $v = $this->getData(self::RESOLUTION_ID);
        return $v !== null ? (int)$v : null;
    }

    public function setResolutionId(int $resolutionId): self
    {
        return $this->setData(self::RESOLUTION_ID, $resolutionId);
    }

    public function getLabel(): string
    {
        return (string)$this->getData(self::LABEL);
    }

    public function setLabel(string $label): self
    {
        return $this->setData(self::LABEL, $label);
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $code): self
    {
        return $this->setData(self::CODE, $code);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
