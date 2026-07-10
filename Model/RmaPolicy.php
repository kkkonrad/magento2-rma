<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaPolicyInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaPolicy as RmaPolicyResource;
use Magento\Framework\Model\AbstractModel;

class RmaPolicy extends AbstractModel implements RmaPolicyInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_policy';

    protected $_idFieldName = 'policy_id';

    protected function _construct(): void
    {
        $this->_init(RmaPolicyResource::class);
    }

    public function getPolicyId(): ?int
    {
        $v = $this->getData(self::POLICY_ID);
        return $v !== null ? (int)$v : null;
    }

    public function setPolicyId(int $policyId): self
    {
        return $this->setData(self::POLICY_ID, $policyId);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getDaysRefund(): int
    {
        return (int)$this->getData(self::DAYS_REFUND);
    }

    public function setDaysRefund(int $days): self
    {
        return $this->setData(self::DAYS_REFUND, $days);
    }

    public function getDaysExchange(): int
    {
        return (int)$this->getData(self::DAYS_EXCHANGE);
    }

    public function setDaysExchange(int $days): self
    {
        return $this->setData(self::DAYS_EXCHANGE, $days);
    }

    public function getDaysRepair(): int
    {
        return (int)$this->getData(self::DAYS_REPAIR);
    }

    public function setDaysRepair(int $days): self
    {
        return $this->setData(self::DAYS_REPAIR, $days);
    }

    public function getDaysVoucher(): int
    {
        return (int)$this->getData(self::DAYS_VOUCHER);
    }

    public function setDaysVoucher(int $days): self
    {
        return $this->setData(self::DAYS_VOUCHER, $days);
    }

    public function getTermsConditions(): ?string
    {
        return $this->getData(self::TERMS_CONDITIONS);
    }

    public function setTermsConditions(?string $termsConditions): self
    {
        return $this->setData(self::TERMS_CONDITIONS, $termsConditions);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
}
