<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Plugin\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class ViewPlugin
{
    /**
     * Add "Create RMA" button to the admin order view action bar if order is complete
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        $order = $subject->getOrder();
        if ($order && $order->getStatus() === 'complete') {
            $url = $subject->getUrl('kkkonrad_rma/rma/create', ['order_id' => $order->getId()]);
            $subject->addButton(
                'kkkonrad_create_rma',
                [
                    'label' => __('Create RMA'),
                    'onclick' => sprintf("location.href='%s'", $url),
                    'class' => 'action-secondary'
                ]
            );
        }
    }
}
