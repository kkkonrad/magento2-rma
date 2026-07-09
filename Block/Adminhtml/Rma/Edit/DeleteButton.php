<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Adminhtml\Rma\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getButtonData(): array
    {
        $data = [];
        $reasonId = (int)$this->request->getParam('reason_id');
        $conditionId = (int)$this->request->getParam('condition_id');
        
        if ($reasonId) {
            $data = [
                'label' => __('Delete Reason'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->urlBuilder->getUrl('*/*/delete', ['reason_id' => $reasonId]) . '\', {data: {}})',
                'sort_order' => 20
            ];
        } elseif ($conditionId) {
            $data = [
                'label' => __('Delete Condition'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->urlBuilder->getUrl('*/*/delete', ['condition_id' => $conditionId]) . '\', {data: {}})',
                'sort_order' => 20
            ];
        }

        return $data;
    }
}
