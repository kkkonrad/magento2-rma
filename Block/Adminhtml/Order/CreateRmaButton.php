<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class CreateRmaButton extends Template
{
    protected $_template = 'Kkkonrad_Rma::order/create_rma_button.phtml';

    public function __construct(
        Context $context,
        private readonly Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?\Magento\Sales\Model\Order
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getCreateRmaUrl(): string
    {
        $order = $this->getOrder();
        return $order
            ? $this->getUrl('kkkonrad_rma/rma/create', ['order_id' => $order->getId()])
            : '';
    }
}
