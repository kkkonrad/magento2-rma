<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\AttachmentUploader;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
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
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory $reasonCollectionFactory,
        private readonly GuestAccessToken $guestAccessToken,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->config->allowGuestRma()) {
            return $result->setData(['success' => false, 'message' => __('Guest Returns are not allowed.')]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => __('Invalid security token. Please refresh and try again.')]);
        }

        $rma = null;
        try {
            $orderId        = (int) $this->request->getPost('order_id');
            $resolutionType = (string) $this->request->getPost('resolution_type');
            $comment        = (string) $this->request->getPost('comment');
            $itemsJson      = (string) $this->request->getPost('items', '[]');
            $itemsData      = json_decode($itemsJson, true) ?? [];
            $requestFiles   = $this->request->getFiles('attachments', []);
            $attachments    = $this->attachmentUploader->validate(is_array($requestFiles) ? $requestFiles : []);

            // Verify guest session authorization matches requested order ID
            $sessionOrderId = (int)$this->customerSession->getGuestRmaOrderId();
            if (!$orderId || $orderId !== $sessionOrderId) {
                throw new LocalizedException(__('Access denied. Please search for your order first.'));
            }

            if (!$resolutionType || empty($itemsData)) {
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

            // Create RMA with customer_id = 0 (Guest)
            $rma = $this->rmaManagement->createFromOrder(
                $orderId,
                0,
                $resolutionType,
                $items,
                $comment,
                (bool) $this->request->getPost('terms_accepted'),
                $attachments !== [],
                false
            );


            // Auto-advance to pending_review
            $rma = $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                null,
                'customer',
                0
            );

            // Handle file uploads
            $this->attachmentUploader->save($rma->getRmaId(), $attachments);

            // Clear guest session variable after creation
            $this->customerSession->setGuestRmaOrderId(null);

            // Generate secure guest tracking link
            $hash = $this->guestAccessToken->issue($rma);
            $this->rmaRepository->save($rma);
            $rma->setData('guest_access_token', $hash);
            $this->eventManager->dispatch('kkkonrad_rma_created', ['rma' => $rma, 'items' => $items]);

            return $result->setData([
                'success'      => true,
                'rma_id'       => $rma->getRmaId(),
                'redirect_url' => $this->url->getUrl('rma/guest/view', [
                    'rma_id' => $rma->getRmaId(),
                    'hash'   => $hash
                ]),
                'message'      => (string) __('Your return request has been submitted successfully.'),
            ]);

        } catch (LocalizedException $e) {
            $this->removeIncompleteRma($rma);
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->removeIncompleteRma($rma);
            $this->logger->error('Guest RMA save error: ' . $e->getMessage(), ['exception' => $e]);
            return $result->setData(['success' => false, 'message' => (string) __('An error occurred. Please try again.')]);
        }
    }

    private function removeIncompleteRma(?RmaInterface $rma): void
    {
        if ($rma === null || !$rma->getRmaId()) {
            return;
        }
        try {
            $this->rmaRepository->deleteById((int) $rma->getRmaId());
        } catch (\Throwable $exception) {
            $this->logger->critical('Failed to roll back incomplete guest RMA.', [
                'rma_id' => $rma->getRmaId(),
                'exception' => $exception,
            ]);
        }
    }

}
