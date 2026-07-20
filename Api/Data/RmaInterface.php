<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Api\Data;

/**
 * RMA Request Interface
 * @api
 */
interface RmaInterface
{
    public const RMA_ID          = 'rma_id';
    public const INCREMENT_ID    = 'increment_id';
    public const ORDER_ID        = 'order_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const CUSTOMER_ID     = 'customer_id';
    public const CUSTOMER_EMAIL  = 'customer_email';
    public const CUSTOMER_NAME   = 'customer_name';
    public const STATUS          = 'status';
    public const RESOLUTION_TYPE = 'resolution_type';
    public const COMMENT         = 'comment';
    public const STORE_ID        = 'store_id';
    public const CREATED_AT      = 'created_at';
    public const UPDATED_AT      = 'updated_at';
    public const RESOLVED_AT     = 'resolved_at';
    public const SHIPPING_LABEL  = 'shipping_label';
    public const RETURN_ADDRESS_ID = 'return_address_id';


    // Status constants
    public const STATUS_NEW             = 'new';
    public const STATUS_PENDING_REVIEW  = 'pending_review';
    public const STATUS_APPROVED        = 'approved';
    public const STATUS_REJECTED        = 'rejected';
    public const STATUS_ITEM_IN_TRANSIT = 'item_in_transit';
    public const STATUS_ITEM_RECEIVED   = 'item_received';
    public const STATUS_RESOLVED        = 'resolved';
    public const STATUS_CLOSED          = 'closed';
    public const STATUS_CANCELLED       = 'cancelled';

    // Resolution type constants
    public const RESOLUTION_REFUND   = 'refund';
    public const RESOLUTION_EXCHANGE = 'exchange';
    public const RESOLUTION_REPAIR   = 'repair';
    public const RESOLUTION_VOUCHER  = 'voucher';

    /** @return int|null */
    public function getRmaId(): ?int;
    /** @return $this */
    public function setRmaId(int $rmaId): self;

    /** @return string|null */
    public function getIncrementId(): ?string;
    /** @return $this */
    public function setIncrementId(string $incrementId): self;

    /** @return int */
    public function getOrderId(): int;
    /** @return $this */
    public function setOrderId(int $orderId): self;

    /** @return string|null */
    public function getOrderIncrementId(): ?string;
    /** @return $this */
    public function setOrderIncrementId(?string $orderIncrementId): self;

    /** @return int|null */
    public function getCustomerId(): ?int;
    /** @return $this */
    public function setCustomerId(?int $customerId): self;

    /** @return string|null */
    public function getCustomerEmail(): ?string;
    /** @return $this */
    public function setCustomerEmail(?string $email): self;

    /** @return string|null */
    public function getCustomerName(): ?string;
    /** @return $this */
    public function setCustomerName(?string $name): self;

    /** @return string */
    public function getStatus(): string;
    /** @return $this */
    public function setStatus(string $status): self;

    /** @return string|null */
    public function getResolutionType(): ?string;
    /** @return $this */
    public function setResolutionType(?string $resolutionType): self;

    /** @return string|null */
    public function getComment(): ?string;
    /** @return $this */
    public function setComment(?string $comment): self;

    /** @return int */
    public function getStoreId(): int;
    /** @return $this */
    public function setStoreId(int $storeId): self;

    /** @return string|null */
    public function getCreatedAt(): ?string;
    /** @return $this */
    public function setCreatedAt(?string $createdAt): self;

    /** @return string|null */
    public function getUpdatedAt(): ?string;
    /** @return $this */
    public function setUpdatedAt(?string $updatedAt): self;

    /** @return string|null */
    public function getResolvedAt(): ?string;
    /** @return $this */
    public function setResolvedAt(?string $resolvedAt): self;

    /** @return string|null */
    public function getShippingLabel(): ?string;
    /** @return $this */
    public function setShippingLabel(?string $shippingLabel): self;

    /** @return int|null */
    public function getReturnAddressId(): ?int;
    /** @return $this */
    public function setReturnAddressId(?int $returnAddressId): self;
}
