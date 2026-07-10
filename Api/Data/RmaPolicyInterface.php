<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Return Policy Interface
 * @api
 */
interface RmaPolicyInterface
{
    public const POLICY_ID        = 'policy_id';
    public const NAME             = 'name';
    public const DAYS_REFUND      = 'days_refund';
    public const DAYS_EXCHANGE    = 'days_exchange';
    public const DAYS_REPAIR      = 'days_repair';
    public const DAYS_VOUCHER     = 'days_voucher';
    public const TERMS_CONDITIONS = 'terms_conditions';
    public const IS_ACTIVE        = 'is_active';

    public function getPolicyId(): ?int;
    public function setPolicyId(int $policyId): self;

    public function getName(): string;
    public function setName(string $name): self;

    public function getDaysRefund(): int;
    public function setDaysRefund(int $days): self;

    public function getDaysExchange(): int;
    public function setDaysExchange(int $days): self;

    public function getDaysRepair(): int;
    public function setDaysRepair(int $days): self;

    public function getDaysVoucher(): int;
    public function setDaysVoucher(int $days): self;

    public function getTermsConditions(): ?string;
    public function setTermsConditions(?string $termsConditions): self;

    public function getIsActive(): bool;
    public function setIsActive(bool $isActive): self;
}
