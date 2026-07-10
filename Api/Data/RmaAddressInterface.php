<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Return Address Interface
 * @api
 */
interface RmaAddressInterface
{
    public const ADDRESS_ID = 'address_id';
    public const NAME       = 'name';
    public const STREET     = 'street';
    public const CITY       = 'city';
    public const POSTCODE   = 'postcode';
    public const COUNTRY_ID = 'country_id';
    public const PHONE      = 'phone';
    public const IS_DEFAULT = 'is_default';
    public const IS_ACTIVE  = 'is_active';

    public function getAddressId(): ?int;
    public function setAddressId(int $addressId): self;

    public function getName(): string;
    public function setName(string $name): self;

    public function getStreet(): string;
    public function setStreet(string $street): self;

    public function getCity(): string;
    public function setCity(string $city): self;

    public function getPostcode(): string;
    public function setPostcode(string $postcode): self;

    public function getCountryId(): string;
    public function setCountryId(string $countryId): self;

    public function getPhone(): ?string;
    public function setPhone(?string $phone): self;

    public function getIsDefault(): bool;
    public function setIsDefault(bool $isDefault): self;

    public function getIsActive(): bool;
    public function setIsActive(bool $isActive): self;
}
