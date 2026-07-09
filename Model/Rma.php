<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\ResourceModel\Rma as RmaResource;
use Magento\Framework\Model\AbstractModel;

class Rma extends AbstractModel implements RmaInterface
{
    protected $_eventPrefix = 'kkkonrad_rma';
    protected $_eventObject = 'rma';

    protected function _construct(): void
    {
        $this->_init(RmaResource::class);
    }

    public function getRmaId(): ?int
    {
        $id = $this->getData(self::RMA_ID);
        return $id !== null ? (int) $id : null;
    }

    public function setRmaId(int $rmaId): self
    {
        return $this->setData(self::RMA_ID, $rmaId);
    }

    public function getIncrementId(): ?string
    {
        return $this->getData(self::INCREMENT_ID);
    }

    public function setIncrementId(string $incrementId): self
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    public function getOrderId(): int
    {
        return (int) $this->getData(self::ORDER_ID);
    }

    public function setOrderId(int $orderId): self
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getOrderIncrementId(): ?string
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    public function setOrderIncrementId(?string $orderIncrementId): self
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    public function getCustomerId(): ?int
    {
        $id = $this->getData(self::CUSTOMER_ID);
        return $id !== null ? (int) $id : null;
    }

    public function setCustomerId(?int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    public function setCustomerEmail(?string $email): self
    {
        return $this->setData(self::CUSTOMER_EMAIL, $email);
    }

    public function getCustomerName(): ?string
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    public function setCustomerName(?string $name): self
    {
        return $this->setData(self::CUSTOMER_NAME, $name);
    }

    public function getStatus(): string
    {
        return (string) $this->getData(self::STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getResolutionType(): ?string
    {
        return $this->getData(self::RESOLUTION_TYPE);
    }

    public function setResolutionType(?string $resolutionType): self
    {
        return $this->setData(self::RESOLUTION_TYPE, $resolutionType);
    }

    public function getComment(): ?string
    {
        return $this->getData(self::COMMENT);
    }

    public function setComment(?string $comment): self
    {
        return $this->setData(self::COMMENT, $comment);
    }

    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(?string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getResolvedAt(): ?string
    {
        return $this->getData(self::RESOLVED_AT);
    }

    public function setResolvedAt(?string $resolvedAt): self
    {
        return $this->setData(self::RESOLVED_AT, $resolvedAt);
    }

    public function getShippingLabel(): ?string
    {
        return $this->getData(self::SHIPPING_LABEL);
    }

    public function setShippingLabel(?string $shippingLabel): self
    {
        return $this->setData(self::SHIPPING_LABEL, $shippingLabel);
    }
}
