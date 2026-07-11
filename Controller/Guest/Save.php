<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Guest;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\GuestAccessToken;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment;
use Kkkonrad\Rma\Model\RmaAttachmentFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    private const ALLOWED_MIME_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'mp4'  => 'video/mp4',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip'  => 'application/zip',
    ];

    public function __construct(
        private readonly HttpRequest $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly RmaManagementInterface $rmaManagement,
        private readonly RmaItemInterfaceFactory $rmaItemFactory,
        private readonly RmaAttachmentFactory $rmaAttachmentFactory,
        private readonly RmaAttachment $rmaAttachmentResource,
        private readonly Config $config,
        private readonly Filesystem $filesystem,
        private readonly Random $random,
        private readonly LoggerInterface $logger,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly UrlInterface $url,
        private readonly \Kkkonrad\Rma\Model\ResourceModel\RmaReason\CollectionFactory $reasonCollectionFactory
        ,private readonly GuestAccessToken $guestAccessToken
        ,private readonly RmaRepositoryInterface $rmaRepository
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

        try {
            $orderId        = (int) $this->request->getPost('order_id');
            $resolutionType = (string) $this->request->getPost('resolution_type');
            $comment        = (string) $this->request->getPost('comment');
            $itemsJson      = (string) $this->request->getPost('items', '[]');
            $itemsData      = json_decode($itemsJson, true) ?? [];

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
                    $files = $this->request->getFiles('attachments') ?: [];
                    $hasFiles = false;
                    if (!empty($files['name'])) {
                        foreach ($files['error'] as $err) {
                            if ($err === UPLOAD_ERR_OK) {
                                $hasFiles = true;
                                break;
                            }
                        }
                    }
                    if (!$hasFiles) {
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
            $rma = $this->rmaManagement->createFromOrder($orderId, 0, $resolutionType, $items, $comment, (bool) $this->request->getPost('terms_accepted'));


            // Auto-advance to pending_review
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                RmaInterface::STATUS_PENDING_REVIEW,
                null,
                'customer',
                0
            );

            // Handle file uploads
            $this->processAttachments($rma->getRmaId());

            // Clear guest session variable after creation
            $this->customerSession->setGuestRmaOrderId(null);

            // Generate secure guest tracking link
            $hash = $this->guestAccessToken->issue($rma);
            $this->rmaRepository->save($rma);

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
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('Guest RMA save error: ' . $e->getMessage(), ['exception' => $e]);
            return $result->setData(['success' => false, 'message' => (string) __('An error occurred. Please try again.')]);
        }
    }

    private function processAttachments(int $rmaId): void
    {
        $files = $this->request->getFiles('attachments') ?: [];
        if (empty($files['name'])) {
            return;
        }

        $mediaDir  = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $uploadDir = 'kkkonrad/rma/' . $rmaId;
        $allowed   = $this->config->getAllowedExtensions();
        $maxSize   = $this->config->getMaxFileSizeMb() * 1024 * 1024;
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($files['name'] as $index => $fileName) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext          = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $tmpPath      = $files['tmp_name'][$index];

            $detectedMime = $finfo->file($tmpPath) ?: 'application/octet-stream';
            $expectedMime = self::ALLOWED_MIME_TYPES[$ext] ?? null;

            if (!isset(self::ALLOWED_MIME_TYPES[$ext])
                || !in_array($ext, $allowed, true)
                || $files['size'][$index] > $maxSize
                || $detectedMime !== $expectedMime
                || !is_uploaded_file($tmpPath)
            ) {
                throw new LocalizedException(__('One or more attachments are invalid.'));
            }

            $safeFileName = $this->random->getUniqueHash() . '.' . $ext;
            $relativePath = $uploadDir . '/' . $safeFileName;

            $mediaDir->create($uploadDir);
            $mediaDir->copyFile($tmpPath, $relativePath);

            /** @var \Kkkonrad\Rma\Model\RmaAttachment $attachment */
            $attachment = $this->rmaAttachmentFactory->create();
            $attachment->setRmaId($rmaId)
                ->setFilePath($relativePath)
                ->setFileName($fileName)
                ->setMimeType($detectedMime)
                ->setFileSize($files['size'][$index]);

            $this->rmaAttachmentResource->save($attachment);
        }
    }
}
