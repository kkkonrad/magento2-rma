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

    /** @return int|null */
    public function getMessageId(): ?int;
    /** @return $this */
    public function setMessageId(int $messageId): self;

    /** @return int */
    public function getRmaId(): int;
    /** @return $this */
    public function setRmaId(int $rmaId): self;

    /** @return string */
    public function getAuthorType(): string;
    /** @return $this */
    public function setAuthorType(string $authorType): self;

    /** @return int|null */
    public function getAuthorId(): ?int;
    /** @return $this */
    public function setAuthorId(?int $authorId): self;

    /** @return string|null */
    public function getAuthorName(): ?string;
    /** @return $this */
    public function setAuthorName(?string $authorName): self;

    /** @return string */
    public function getMessage(): string;
    /** @return $this */
    public function setMessage(string $message): self;

    /** @return bool */
    public function getIsInternal(): bool;
    /** @return $this */
    public function setIsInternal(bool $isInternal): self;

    /** @return string|null */
    public function getCreatedAt(): ?string;
    /** @return $this */
    public function setCreatedAt(?string $createdAt): self;
}
