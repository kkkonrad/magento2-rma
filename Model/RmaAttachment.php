<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaAttachmentInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as RmaAttachmentResource;
use Magento\Framework\Model\AbstractModel;

class RmaAttachment extends AbstractModel implements RmaAttachmentInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_attachment';

    protected function _construct(): void
    {
        $this->_init(RmaAttachmentResource::class);
    }

    public function getAttachmentId(): ?int { $v = $this->getData(self::ATTACHMENT_ID); return $v !== null ? (int)$v : null; }
    public function setAttachmentId(int $attachmentId): self { return $this->setData(self::ATTACHMENT_ID, $attachmentId); }
    public function getRmaId(): int { return (int)$this->getData(self::RMA_ID); }
    public function setRmaId(int $rmaId): self { return $this->setData(self::RMA_ID, $rmaId); }
    public function getItemId(): ?int { $v = $this->getData(self::ITEM_ID); return $v !== null ? (int)$v : null; }
    public function setItemId(?int $itemId): self { return $this->setData(self::ITEM_ID, $itemId); }
    public function getMessageId(): ?int { $v = $this->getData(self::MESSAGE_ID); return $v !== null ? (int)$v : null; }
    public function setMessageId(?int $messageId): self { return $this->setData(self::MESSAGE_ID, $messageId); }
    public function getFilePath(): string { return (string)$this->getData(self::FILE_PATH); }
    public function setFilePath(string $filePath): self { return $this->setData(self::FILE_PATH, $filePath); }
    public function getFileName(): string { return (string)$this->getData(self::FILE_NAME); }
    public function setFileName(string $fileName): self { return $this->setData(self::FILE_NAME, $fileName); }
    public function getMimeType(): string { return (string)$this->getData(self::MIME_TYPE); }
    public function setMimeType(string $mimeType): self { return $this->setData(self::MIME_TYPE, $mimeType); }
    public function getFileSize(): int { return (int)$this->getData(self::FILE_SIZE); }
    public function setFileSize(int $fileSize): self { return $this->setData(self::FILE_SIZE, $fileSize); }
    public function getCreatedAt(): ?string { return $this->getData(self::CREATED_AT); }
    public function setCreatedAt(?string $createdAt): self { return $this->setData(self::CREATED_AT, $createdAt); }
}
