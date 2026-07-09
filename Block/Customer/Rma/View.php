<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Customer\Rma;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment\CollectionFactory as AttachmentCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage\CollectionFactory as MessageCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory\CollectionFactory as HistoryCollectionFactory;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class View extends Template
{
    protected $_template = 'Kkkonrad_Rma::customer/rma/view.phtml';

    private ?RmaInterface $rma = null;

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly CustomerSession $customerSession,
        private readonly MessageCollectionFactory $messageCollectionFactory,
        private readonly HistoryCollectionFactory $historyCollectionFactory,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRma(): ?RmaInterface
    {
        if ($this->rma === null) {
            $rmaId      = (int) $this->getRequest()->getParam('rma_id');
            $customerId = (int) $this->customerSession->getCustomerId();

            if (!$rmaId || !$customerId) {
                return null;
            }

            try {
                $rma = $this->rmaRepository->getById($rmaId);

                // Security: customer must own this RMA
                if ((int) $rma->getCustomerId() !== $customerId) {
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
        $rma = $this->getRma();
        if (!$rma) {
            return [];
        }

        $collection = $this->messageCollectionFactory->create();
        $collection->addFieldToFilter('rma_id', $rma->getRmaId())
            ->addFieldToFilter('is_internal', ['eq' => 0]) // Customer-visible only
            ->setOrder('created_at', 'ASC');

        return $collection->getItems();
    }

    public function getStatusHistory(): array
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

    public function getStatusLabel(string $status): string
    {
        return (string) __(Status::getLabel($status));
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
        return $this->getUrl('rma/index/addMessage');
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
