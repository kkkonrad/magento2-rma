<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Adminhtml\Rma\Edit;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment\CollectionFactory as AttachmentCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory as ItemCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage\CollectionFactory as MessageCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory\CollectionFactory as HistoryCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Model\Source\Status as StatusSource;
use Kkkonrad\Rma\Model\StatusValidator;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

class Detail extends Template
{
    private ?RmaInterface $rma = null;

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly MessageCollectionFactory $messageCollectionFactory,
        private readonly HistoryCollectionFactory $historyCollectionFactory,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        private readonly StatusValidator $statusValidator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRma(): ?RmaInterface
    {
        if ($this->rma === null) {
            $rmaId = (int)$this->getRequest()->getParam('rma_id');
            if ($rmaId) {
                try {
                    $this->rma = $this->rmaRepository->getById($rmaId);
                } catch (NoSuchEntityException) {
                    $this->rma = null;
                }
            }
        }
        return $this->rma;
    }

    public function getOrder(): ?\Magento\Sales\Api\Data\OrderInterface
    {
        $rma = $this->getRma();
        if ($rma) {
            try {
                return $this->orderRepository->get($rma->getOrderId());
            } catch (NoSuchEntityException) {
                return null;
            }
        }
        return null;
    }

    public function getItems(): array
    {
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }
        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId());
        return $collection->getItems();
    }

    public function getMessages(): array
    {
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }
        $collection = $this->messageCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId())
            ->setOrder('created_at', 'ASC');
        return $collection->getItems();
    }

    public function getHistory(): array
    {
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }
        $collection = $this->historyCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId())
            ->setOrder('created_at', 'ASC');
        return $collection->getItems();
    }

    public function getAttachments(): array
    {
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }
        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId());
        return $collection->getItems();
    }

    public function getReasons(): array
    {
        $collection = $this->reasonCollectionFactory->create();
        $reasons = [];
        foreach ($collection as $reason) {
            $reasons[$reason->getReasonId()] = $reason->getLabel();
        }
        return $reasons;
    }

    public function getConditions(): array
    {
        $collection = $this->conditionCollectionFactory->create();
        $conditions = [];
        foreach ($collection as $condition) {
            $conditions[$condition->getConditionId()] = $condition->getLabel();
        }
        return $conditions;
    }

    public function getStatusLabel(string $status): string
    {
        return (string)__(StatusSource::getLabel($status));
    }

    public function getStatusValidator(): StatusValidator
    {
        return $this->statusValidator;
    }

    public function getSaveMessageUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/addMessage');
    }

    public function getChangeStatusUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/changeStatus');
    }

    public function getApproveUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/approve');
    }

    public function getRejectUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/reject');
    }

    public function getCancelUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/cancel');
    }

    public function getUploadShippingLabelUrl(): string
    {
        return $this->getUrl('kkkonrad_rma/rma/uploadShippingLabel');
    }

    public function getMediaUrl(string $filePath): string
    {
        $baseMediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if (str_starts_with($filePath, 'kkkonrad/rma/')) {
            return $baseMediaUrl . $filePath;
        }
        return $baseMediaUrl . 'kkkonrad/rma/' . ltrim($filePath, '/');
    }
}
