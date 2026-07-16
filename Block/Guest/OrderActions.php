<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Guest;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestRmaLocator;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;

class OrderActions extends Template
{
    private ?RmaInterface $latestRma = null;
    private bool $latestRmaLoaded = false;

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly Config $config,
        private readonly GuestRmaLocator $guestRmaLocator,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly Status $statusSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function canDisplay(): bool
    {
        $order = $this->getOrder();
        if (!$order || !$order->getCustomerIsGuest()) {
            return false;
        }

        $storeId = (int) $order->getStoreId();
        return $this->config->isEnabled($storeId) && $this->config->allowGuestRma($storeId);
    }

    public function canCreate(): bool
    {
        $order = $this->getOrder();
        return $order !== null
            && $this->guestRmaLocator->getActiveForOrder((int) $order->getEntityId()) === null
            && $this->rmaManagement->isOrderEligibleForRma((int) $order->getEntityId(), 0);
    }

    public function getLatestRma(): ?RmaInterface
    {
        if (!$this->latestRmaLoaded) {
            $order = $this->getOrder();
            $this->latestRma = $order
                ? $this->guestRmaLocator->getLatestForOrder((int) $order->getEntityId())
                : null;
            $this->latestRmaLoaded = true;
        }

        return $this->latestRma;
    }

    public function getStatusLabel(string $status): string
    {
        return (string) $this->statusSource->getLabel($status);
    }

    public function getAccessUrl(): string
    {
        return $this->getUrl('rma/guest/access');
    }

    public function getOrderId(): int
    {
        return (int) ($this->getOrder()?->getEntityId() ?? 0);
    }

    private function getOrder(): ?OrderInterface
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof OrderInterface ? $order : null;
    }
}
