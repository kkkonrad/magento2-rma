<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Status History Interface
 * @api
 */
interface RmaStatusHistoryInterface
{
    public const HISTORY_ID    = 'history_id';
    public const RMA_ID        = 'rma_id';
    public const STATUS_FROM   = 'status_from';
    public const STATUS_TO     = 'status_to';
    public const COMMENT       = 'comment';
    public const CREATED_BY    = 'created_by';
    public const CREATED_BY_ID = 'created_by_id';
    public const CREATED_AT    = 'created_at';

    public function getHistoryId(): ?int;
    public function setHistoryId(int $historyId): self;

    public function getRmaId(): int;
    public function setRmaId(int $rmaId): self;

    public function getStatusFrom(): ?string;
    public function setStatusFrom(?string $statusFrom): self;

    public function getStatusTo(): string;
    public function setStatusTo(string $statusTo): self;

    public function getComment(): ?string;
    public function setComment(?string $comment): self;

    public function getCreatedBy(): string;
    public function setCreatedBy(string $createdBy): self;

    public function getCreatedById(): ?int;
    public function setCreatedById(?int $createdById): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(?string $createdAt): self;
}
