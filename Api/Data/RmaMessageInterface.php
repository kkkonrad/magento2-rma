<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Message Interface
 * @api
 */
interface RmaMessageInterface
{
    public const MESSAGE_ID  = 'message_id';
    public const RMA_ID      = 'rma_id';
    public const AUTHOR_TYPE = 'author_type';
    public const AUTHOR_ID   = 'author_id';
    public const AUTHOR_NAME = 'author_name';
    public const MESSAGE     = 'message';
    public const IS_INTERNAL = 'is_internal';
    public const CREATED_AT  = 'created_at';

    public const AUTHOR_ADMIN    = 'admin';
    public const AUTHOR_CUSTOMER = 'customer';
    public const AUTHOR_SYSTEM   = 'system';

    public function getMessageId(): ?int;
    public function setMessageId(int $messageId): self;

    public function getRmaId(): int;
    public function setRmaId(int $rmaId): self;

    public function getAuthorType(): string;
    public function setAuthorType(string $authorType): self;

    public function getAuthorId(): ?int;
    public function setAuthorId(?int $authorId): self;

    public function getAuthorName(): ?string;
    public function setAuthorName(?string $authorName): self;

    public function getMessage(): string;
    public function setMessage(string $message): self;

    public function getIsInternal(): bool;
    public function setIsInternal(bool $isInternal): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(?string $createdAt): self;
}
