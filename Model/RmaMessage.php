<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaMessageInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage as RmaMessageResource;
use Magento\Framework\Model\AbstractModel;

class RmaMessage extends AbstractModel implements RmaMessageInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_message';

    protected function _construct(): void
    {
        $this->_init(RmaMessageResource::class);
    }

    public function getMessageId(): ?int { $v = $this->getData(self::MESSAGE_ID); return $v !== null ? (int)$v : null; }
    public function setMessageId(int $messageId): self { return $this->setData(self::MESSAGE_ID, $messageId); }
    public function getRmaId(): int { return (int)$this->getData(self::RMA_ID); }
    public function setRmaId(int $rmaId): self { return $this->setData(self::RMA_ID, $rmaId); }
    public function getAuthorType(): string { return (string)$this->getData(self::AUTHOR_TYPE); }
    public function setAuthorType(string $authorType): self { return $this->setData(self::AUTHOR_TYPE, $authorType); }
    public function getAuthorId(): ?int { $v = $this->getData(self::AUTHOR_ID); return $v !== null ? (int)$v : null; }
    public function setAuthorId(?int $authorId): self { return $this->setData(self::AUTHOR_ID, $authorId); }
    public function getAuthorName(): ?string { return $this->getData(self::AUTHOR_NAME); }
    public function setAuthorName(?string $authorName): self { return $this->setData(self::AUTHOR_NAME, $authorName); }
    public function getMessage(): string { return (string)$this->getData(self::MESSAGE); }
    public function setMessage(string $message): self { return $this->setData(self::MESSAGE, $message); }
    public function getIsInternal(): bool { return (bool)$this->getData(self::IS_INTERNAL); }
    public function setIsInternal(bool $isInternal): self { return $this->setData(self::IS_INTERNAL, $isInternal); }
    public function getCreatedAt(): ?string { return $this->getData(self::CREATED_AT); }
    public function setCreatedAt(?string $createdAt): self { return $this->setData(self::CREATED_AT, $createdAt); }
}
