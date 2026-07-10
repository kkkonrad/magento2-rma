<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaAddressInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAddress as RmaAddressResource;
use Magento\Framework\Model\AbstractModel;

class RmaAddress extends AbstractModel implements RmaAddressInterface
{
    protected $_eventPrefix = 'kkkonrad_rma_address';

    protected $_idFieldName = 'address_id';

    protected function _construct(): void
    {
        $this->_init(RmaAddressResource::class);
    }

    public function getAddressId(): ?int
    {
        $v = $this->getData(self::ADDRESS_ID);
        return $v !== null ? (int)$v : null;
    }

    public function setAddressId(int $addressId): self
    {
        return $this->setData(self::ADDRESS_ID, $addressId);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getStreet(): string
    {
        return (string)$this->getData(self::STREET);
    }

    public function setStreet(string $street): self
    {
        return $this->setData(self::STREET, $street);
    }

    public function getCity(): string
    {
        return (string)$this->getData(self::CITY);
    }

    public function setCity(string $city): self
    {
        return $this->setData(self::CITY, $city);
    }

    public function getPostcode(): string
    {
        return (string)$this->getData(self::POSTCODE);
    }

    public function setPostcode(string $postcode): self
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    public function getCountryId(): string
    {
        return (string)$this->getData(self::COUNTRY_ID);
    }

    public function setCountryId(string $countryId): self
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    public function getPhone(): ?string
    {
        $v = $this->getData(self::PHONE);
        return $v !== null ? (string)$v : null;
    }

    public function setPhone(?string $phone): self
    {
        return $this->setData(self::PHONE, $phone);
    }

    public function getIsDefault(): bool
    {
        return (bool)$this->getData(self::IS_DEFAULT);
    }

    public function setIsDefault(bool $isDefault): self
    {
        return $this->setData(self::IS_DEFAULT, $isDefault);
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
