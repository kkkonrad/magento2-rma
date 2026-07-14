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
        $entities = [
            'reason_id' => __('Delete Reason'),
            'condition_id' => __('Delete Condition'),
            'address_id' => __('Delete Address'),
            'policy_id' => __('Delete Policy'),
            'resolution_id' => __('Delete Resolution'),
            'reply_id' => __('Delete Canned Reply'),
        ];

        foreach ($entities as $parameter => $label) {
            $entityId = (int)$this->request->getParam($parameter);
            if (!$entityId) {
                continue;
            }

            return [
                'label' => $label,
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->urlBuilder->getUrl('*/*/delete', [$parameter => $entityId])
                    . '\', {data: {}})',
                'sort_order' => 20,
            ];
        }

        return [];
    }
}
