<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Guest;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment\CollectionFactory as AttachmentCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage\CollectionFactory as MessageCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory\CollectionFactory as HistoryCollectionFactory;
use Kkkonrad\Rma\Model\Source\Status;
use Kkkonrad\Rma\Model\StatusValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class View extends Template
{
    protected $_template = 'Kkkonrad_Rma::guest/view.phtml';

    private ?RmaInterface $rma = null;
    private ?array $messages = null;
    private ?array $statusHistory = null;
    private ?array $attachments = null;

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly MessageCollectionFactory $messageCollectionFactory,
        private readonly HistoryCollectionFactory $historyCollectionFactory,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        private readonly Status $statusSource,
        private readonly Config $config,
        private readonly StatusValidator $statusValidator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRma(): ?RmaInterface
    {
        if ($this->rma === null) {
            $rmaId = (int) $this->getRequest()->getParam('rma_id');
            $hash  = (string) $this->getRequest()->getParam('hash');

            if (!$rmaId || !$hash) {
                return null;
            }

            try {
                $rma = $this->rmaRepository->getById($rmaId);
                $expectedHash = md5($rma->getRmaId() . $rma->getCustomerEmail() . $rma->getCreatedAt());
                if ($hash !== $expectedHash) {
                    return null;
                }
                $this->rma = $rma;
            } catch (NoSuchEntityException) {
                return null;
            }
        }
        return $this->rma;
    }

    public function getMessages(): array
    {
        if ($this->messages !== null) {
            return $this->messages;
        }

        $rma = $this->getRma();
        if (!$rma) {
            return $this->messages = [];
        }

        $collection = $this->messageCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId())
            ->addFieldToFilter('is_internal', ['eq' => 0])
            ->setOrder('created_at', 'ASC');

        return $this->messages = array_values($collection->getItems());
    }

    public function getStatusHistory(): array
    {
        if ($this->statusHistory !== null) {
            return $this->statusHistory;
        }

        $rma = $this->getRma();
        if (!$rma) {
            return $this->statusHistory = [];
        }

        $collection = $this->historyCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId())
            ->setOrder('created_at', 'ASC');

        return $this->statusHistory = array_values($collection->getItems());
    }

    public function getAttachments(): array
    {
        if ($this->attachments !== null) {
            return $this->attachments;
        }

        $rma = $this->getRma();
        if (!$rma) {
            return $this->attachments = [];
        }

        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId());

        return $this->attachments = array_values($collection->getItems());
    }

    public function getStatusLabel(string $status): string
    {
        return (string) __($this->statusSource->getLabel($status));
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'new'             => 'bg-blue-100 text-blue-800',
            'pending_review'  => 'bg-yellow-100 text-yellow-800',
            'approved'        => 'bg-green-100 text-green-800',
            'rejected'        => 'bg-red-100 text-red-800',
            'item_in_transit' => 'bg-indigo-100 text-indigo-800',
            'item_received'   => 'bg-purple-100 text-purple-800',
            'resolved'        => 'bg-emerald-100 text-emerald-800',
            'closed'          => 'bg-gray-100 text-gray-600',
            'cancelled'       => 'bg-gray-100 text-gray-500',
            default           => 'bg-gray-100 text-gray-600',
        };
    }

    public function getAddMessageUrl(): string
    {
        return $this->getUrl('rma/guest/addMessage');
    }

    public function getCancelUrl(): string
    {
        return $this->getUrl('rma/guest/cancel');
    }

    public function getMediaUrl(string $filePath): string
    {
        $baseMediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if (str_starts_with($filePath, 'kkkonrad/rma/')) {
            return $baseMediaUrl . $filePath;
        }
        return $baseMediaUrl . 'kkkonrad/rma/' . ltrim($filePath, '/');
    }

    public function getRmaHash(): string
    {
        $rma = $this->getRma();
        return $rma ? md5($rma->getRmaId() . $rma->getCustomerEmail() . $rma->getCreatedAt()) : '';
    }

    public function canCustomerCancel(): bool
    {
        $rma = $this->getRma();
        if (!$rma) {
            return false;
        }
        if (!$this->config->canCustomerCancelRma((int) $rma->getStoreId())) {
            return false;
        }
        return !$this->statusValidator->isTerminalStatus($rma->getStatus());
    }
}
