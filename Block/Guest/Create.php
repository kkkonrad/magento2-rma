<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Block\Guest;

use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\DictionaryLabelTranslator;
use Kkkonrad\Rma\Model\ResourceModel\RmaCondition\CollectionFactory as ConditionCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory as ReasonCollectionFactory;
use Kkkonrad\Rma\Model\Source\ResolutionType;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Create extends Template
{
    protected $_template = 'Kkkonrad_Rma::guest/create.phtml';

    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ReasonCollectionFactory $reasonCollectionFactory,
        private readonly ConditionCollectionFactory $conditionCollectionFactory,
        private readonly ResolutionType $resolutionTypeSource,
        private readonly Config $config,
        private readonly \Magento\Framework\Data\Form\FormKey $formKey,
        private readonly \Magento\Cms\Helper\Page $pageHelper,
        private readonly DictionaryLabelTranslator $dictionaryLabelTranslator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isTermsEnabled(): bool
    {
        return $this->config->isTermsEnabled((int)$this->_storeManager->getStore()->getId());
    }

    public function getTermsPageUrl(): string
    {
        $pageId = $this->config->getTermsCmsPage((int)$this->_storeManager->getStore()->getId());
        return $pageId ? $this->pageHelper->getPageUrl($pageId) : '';
    }


    public function getEligibleOrders(): array
    {
        $orderId = (int)$this->customerSession->getGuestRmaOrderId();
        if (!$orderId) {
            return [];
        }
        try {
            $order = $this->orderRepository->get($orderId);
            return [$order];
        } catch (NoSuchEntityException) {
            return [];
        }
    }

    public function getReasons(): array
    {
        $collection = $this->reasonCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1])
            ->setOrder('sort_order', 'ASC');

        $options = [];
        foreach ($collection as $reason) {
            $options[] = [
                'value' => $reason->getReasonId(),
                'label' => (string) $this->dictionaryLabelTranslator->getReasonLabel(
                    (string) $reason->getCode(),
                    (string) $reason->getLabel()
                ),
            ];
        }
        return $options;
    }

    public function getConditions(): array
    {
        $collection = $this->conditionCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1])
            ->setOrder('sort_order', 'ASC');

        $options = [];
        foreach ($collection as $condition) {
            $options[] = [
                'value' => $condition->getConditionId(),
                'label' => (string) $this->dictionaryLabelTranslator->getConditionLabel(
                    (string) $condition->getCode(),
                    (string) $condition->getLabel()
                ),
            ];
        }
        return $options;
    }

    public function getResolutionTypes(): array
    {
        return $this->resolutionTypeSource->toOptionArray();
    }

    public function getMaxFileSizeMb(): int
    {
        return $this->config->getMaxFileSizeMb();
    }

    public function getAllowedExtensions(): string
    {
        return implode(',', $this->config->getAllowedExtensions());
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('rma/guest/save');
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getReasonsRequireImageMap(): array
    {
        $collection = $this->reasonCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $map = [];
        foreach ($collection as $reason) {
            if ($reason->getData('require_image')) {
                $map[] = (int)$reason->getReasonId();
            }
        }
        return $map;
    }
}
