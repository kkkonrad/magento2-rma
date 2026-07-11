<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Return Resolution Interface
 * @api
 */
interface RmaResolutionInterface
{
    public const RESOLUTION_ID = 'resolution_id';
    public const LABEL         = 'label';
    public const CODE          = 'code';
    public const SORT_ORDER    = 'sort_order';
    public const IS_ACTIVE     = 'is_active';

    public function getResolutionId(): ?int;
    public function setResolutionId(int $resolutionId): self;

    public function getLabel(): string;
    public function setLabel(string $label): self;

    public function getCode(): string;
    public function setCode(string $code): self;

    public function getSortOrder(): int;
    public function setSortOrder(int $sortOrder): self;

    public function getIsActive(): bool;
    public function setIsActive(bool $isActive): self;
}
