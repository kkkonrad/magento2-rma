<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Plugin\Adminhtml\Order;

use Kkkonrad\Rma\Model\Config;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class ViewPlugin
{
    public function __construct(
        private readonly Config $config
    ) {
    }
    /**
     * Add "Create RMA" button to the admin order view action bar if order is complete
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        $order = $subject->getOrder();
        if (!$order) {
            return;
        }

        // Fix R3-7: Use Config-driven allowed statuses instead of hardcoded 'complete'
        $storeId = (int) $order->getStoreId();
        $allowedStatuses = $this->config->getAllowedOrderStatuses($storeId);

        if (in_array($order->getStatus(), $allowedStatuses, true)) {
            $url = $subject->getUrl('kkkonrad_rma/rma/create', ['order_id' => $order->getId()]);
            $subject->addButton(
                'kkkonrad_create_rma',
                [
                    'label'   => __('Create RMA'),
                    'onclick' => sprintf("location.href='%s'", $url),
                    'class'   => 'action-secondary'
                ]
            );
        }
    }
}
