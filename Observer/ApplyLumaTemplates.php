<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Observer;

use Kkkonrad\Rma\Model\ThemeCompatibility;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

class ApplyLumaTemplates implements ObserverInterface
{
    private const TEMPLATES = [
        'customer-account-navigation-rma-link' => 'Kkkonrad_Rma::luma/navigation-link.phtml',
        'kkkonrad_rma_list' => 'Kkkonrad_Rma::luma/list.phtml',
        'kkkonrad_rma_create' => 'Kkkonrad_Rma::luma/create.phtml',
        'kkkonrad_rma_view' => 'Kkkonrad_Rma::luma/view.phtml',
        'kkkonrad_rma_guest_create' => 'Kkkonrad_Rma::luma/create.phtml',
        'kkkonrad_rma_guest_view' => 'Kkkonrad_Rma::luma/view.phtml',
        'kkkonrad_rma_guest_order_actions' => 'Kkkonrad_Rma::luma/order-actions.phtml',
    ];

    public function __construct(private readonly ThemeCompatibility $themeCompatibility)
    {
    }

    public function execute(Observer $observer): void
    {
        if ($this->themeCompatibility->isHyva()) {
            return;
        }

        $layout = $observer->getData('layout');
        if (!$layout instanceof LayoutInterface) {
            return;
        }

        foreach (self::TEMPLATES as $blockName => $template) {
            $block = $layout->getBlock($blockName);
            if ($block && method_exists($block, 'setTemplate')) {
                $block->setTemplate($template);
            }
        }
    }
}
