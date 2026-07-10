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

    public function getRmaId(): ?int;
    public function setRmaId(int $rmaId): self;

    public function getIncrementId(): ?string;
    public function setIncrementId(string $incrementId): self;

    public function getOrderId(): int;
    public function setOrderId(int $orderId): self;

    public function getOrderIncrementId(): ?string;
    public function setOrderIncrementId(?string $orderIncrementId): self;

    public function getCustomerId(): ?int;
    public function setCustomerId(?int $customerId): self;

    public function getCustomerEmail(): ?string;
    public function setCustomerEmail(?string $email): self;

    public function getCustomerName(): ?string;
    public function setCustomerName(?string $name): self;

    public function getStatus(): string;
    public function setStatus(string $status): self;

    public function getResolutionType(): ?string;
    public function setResolutionType(?string $resolutionType): self;

    public function getComment(): ?string;
    public function setComment(?string $comment): self;

    public function getStoreId(): int;
    public function setStoreId(int $storeId): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(?string $createdAt): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(?string $updatedAt): self;

    public function getResolvedAt(): ?string;
    public function setResolvedAt(?string $resolvedAt): self;

    public function getShippingLabel(): ?string;
    public function setShippingLabel(?string $shippingLabel): self;

    public function getReturnAddressId(): ?int;
    public function setReturnAddressId(?int $returnAddressId): self;
}

