<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Canned Reply Interface
 * @api
 */
interface CannedReplyInterface
{
    public const REPLY_ID  = 'reply_id';
    public const TITLE     = 'title';
    public const TEXT      = 'text';
    public const IS_ACTIVE = 'is_active';

    public function getReplyId(): ?int;
    public function setReplyId(int $replyId): self;

    public function getTitle(): string;
    public function setTitle(string $title): self;

    public function getText(): string;
    public function setText(string $text): self;

    public function getIsActive(): bool;
    public function setIsActive(bool $isActive): self;
}
