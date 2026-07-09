<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Attachment Interface
 * @api
 */
interface RmaAttachmentInterface
{
    public const ATTACHMENT_ID = 'attachment_id';
    public const RMA_ID        = 'rma_id';
    public const ITEM_ID       = 'item_id';
    public const MESSAGE_ID    = 'message_id';
    public const FILE_PATH     = 'file_path';
    public const FILE_NAME     = 'file_name';
    public const MIME_TYPE     = 'mime_type';
    public const FILE_SIZE     = 'file_size';
    public const CREATED_AT    = 'created_at';

    public function getAttachmentId(): ?int;
    public function setAttachmentId(int $attachmentId): self;

    public function getRmaId(): int;
    public function setRmaId(int $rmaId): self;

    public function getItemId(): ?int;
    public function setItemId(?int $itemId): self;

    public function getMessageId(): ?int;
    public function setMessageId(?int $messageId): self;

    public function getFilePath(): string;
    public function setFilePath(string $filePath): self;

    public function getFileName(): string;
    public function setFileName(string $fileName): self;

    public function getMimeType(): string;
    public function setMimeType(string $mimeType): self;

    public function getFileSize(): int;
    public function setFileSize(int $fileSize): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(?string $createdAt): self;
}
