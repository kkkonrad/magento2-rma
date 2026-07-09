<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Adminhtml\Rma\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly \Magento\Framework\UrlInterface $urlBuilder
    ) {
    }

    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->urlBuilder->getUrl('*/*/index')),
            'class' => 'back',
            'sort_order' => 10
        ];
    }
}
