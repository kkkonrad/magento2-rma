<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\AttachmentUploader;
use Kkkonrad\Rma\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    public function __construct(
        private readonly HttpRequest $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaItemInterfaceFactory $rmaItemFactory,
        private readonly Config $config,
        private readonly AttachmentUploader $attachmentUploader,
        private readonly LoggerInterface $logger,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly UrlInterface $url,
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory $reasonCollectionFactory
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Please log in to submit a return request.')]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Invalid security token. Please refresh and try again.')]);
        }

        try {
            $customerId     = (int) $this->customerSession->getCustomerId();
            $orderId        = (int) $this->request->getPost('order_id');
            $resolutionType = (string) $this->request->getPost('resolution_type');
            $comment        = (string) $this->request->getPost('comment');
            $itemsJson      = (string) $this->request->getPost('items', '[]');
            $itemsData      = json_decode($itemsJson, true) ?? [];
            $requestFiles   = $this->request->getFiles('attachments', []);
            $attachments    = $this->attachmentUploader->validate(is_array($requestFiles) ? $requestFiles : []);

            if (!$orderId || !$resolutionType || empty($itemsData)) {
                throw new LocalizedException(__('Please fill in all required fields.'));
            }
            if ($this->config->isTermsEnabled() && !$this->request->getPost('terms_accepted')) {
                throw new LocalizedException(__('You must accept the return terms and conditions.'));
            }

            // Reason-Specific Attachment validation (backend)
            $requiredImageReasonIds = [];
            foreach ($itemsData as $itemData) {
                if (!empty($itemData['reason_id'])) {
                    $requiredImageReasonIds[] = (int)$itemData['reason_id'];
                }
            }

            if (!empty($requiredImageReasonIds)) {
                $reasonCollection = $this->reasonCollectionFactory->create();
                $reasonCollection->addFieldToFilter('reason_id', ['in' => $requiredImageReasonIds])
                    ->addFieldToFilter('require_image', 1);

                if ($reasonCollection->getSize() > 0) {
                    if ($attachments === []) {
                        throw new LocalizedException(__('At least one of your selected return reasons requires photographic verification. Please attach at least one image or document.'));
                    }
                }
            }

            // Build RmaItem objects
            $items = [];
            foreach ($itemsData as $itemData) {
                /** @var \Kkkonrad\Rma\Api\Data\RmaItemInterface $item */
                $item = $this->rmaItemFactory->create();
                $item->setOrderItemId((int)($itemData['order_item_id'] ?? 0))
                    ->setQty((float)($itemData['qty'] ?? 1))
                    ->setReasonId(isset($itemData['reason_id']) && $itemData['reason_id'] !== ''
                        ? (int)$itemData['reason_id'] : null)
                    ->setConditionId(isset($itemData['condition_id']) && $itemData['condition_id'] !== ''
                        ? (int)$itemData['condition_id'] : null);
                $items[] = $item;
            }

            $rma = $this->rmaManagement->createFromOrder($orderId, $customerId, $resolutionType, $items, $comment, (bool) $this->request->getPost('terms_accepted'));


            // Auto-advance to pending_review so support team is notified immediately
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                null,
                'customer',
                $customerId
            );

            // Handle file uploads
            $this->attachmentUploader->save($rma->getRmaId(), $attachments);

            return $result->setData([
                'success'      => true,
                'rma_id'       => $rma->getRmaId(),
                'redirect_url' => $this->url->getUrl('rma/index/view', ['rma_id' => $rma->getRmaId()]),
                'message'      => (string) __('Your return request has been submitted successfully.'),
            ]);

        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('RMA save error: ' . $e->getMessage(), ['exception' => $e]);
            return $result->setData(['success' => false, 'message' => (string) __('An error occurred. Please try again.')]);
        }
    }

}
