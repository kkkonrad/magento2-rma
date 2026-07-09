<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaStatusHistoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory as RmaStatusHistoryResource;
use Magento\Framework\Model\AbstractModel;

class RmaStatusHistory extends AbstractModel implements RmaStatusHistoryInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_status_history';

    protected function _construct(): void
    {
        $this->_init(RmaStatusHistoryResource::class);
    }

    public function getHistoryId(): ?int { $v = $this->getData(self::HISTORY_ID); return $v !== null ? (int)$v : null; }
    public function setHistoryId(int $historyId): self { return $this->setData(self::HISTORY_ID, $historyId); }
    public function getRmaId(): int { return (int)$this->getData(self::RMA_ID); }
    public function setRmaId(int $rmaId): self { return $this->setData(self::RMA_ID, $rmaId); }
    public function getStatusFrom(): ?string { return $this->getData(self::STATUS_FROM); }
    public function setStatusFrom(?string $statusFrom): self { return $this->setData(self::STATUS_FROM, $statusFrom); }
    public function getStatusTo(): string { return (string)$this->getData(self::STATUS_TO); }
    public function setStatusTo(string $statusTo): self { return $this->setData(self::STATUS_TO, $statusTo); }
    public function getComment(): ?string { return $this->getData(self::COMMENT); }
    public function setComment(?string $comment): self { return $this->setData(self::COMMENT, $comment); }
    public function getCreatedBy(): string { return (string)$this->getData(self::CREATED_BY); }
    public function setCreatedBy(string $createdBy): self { return $this->setData(self::CREATED_BY, $createdBy); }
    public function getCreatedById(): ?int { $v = $this->getData(self::CREATED_BY_ID); return $v !== null ? (int)$v : null; }
    public function setCreatedById(?int $createdById): self { return $this->setData(self::CREATED_BY_ID, $createdById); }
    public function getCreatedAt(): ?string { return $this->getData(self::CREATED_AT); }
    public function setCreatedAt(?string $createdAt): self { return $this->setData(self::CREATED_AT, $createdAt); }
}
