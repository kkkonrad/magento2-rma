<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Index;

use Kkkonrad\Rma\Api\Data\RmaItemInterfaceFactory;
use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment;
use Kkkonrad\Rma\Model\RmaAttachmentFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Save implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
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
        private readonly UrlInterface $url
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
            $customerId    = (int) $this->customerSession->getCustomerId();
            $orderId       = (int) $this->request->getPost('order_id');
            $resolutionType = (string) $this->request->getPost('resolution_type');
            $comment       = (string) $this->request->getPost('comment');
            $itemsJson     = (string) $this->request->getPost('items', '[]');
            $itemsData     = json_decode($itemsJson, true) ?? [];

            if (!$orderId || !$resolutionType || empty($itemsData)) {
                throw new LocalizedException(__('Please fill in all required fields.'));
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

            $rma = $this->rmaManagement->createFromOrder($orderId, $customerId, $resolutionType, $items, $comment);

            // Auto-advance to pending_review so support team is notified immediately
            $this->rmaManagement->changeStatus(
                $rma->getRmaId(),
                'pending_review',
                null,
                'customer',
                $customerId
            );

            // Handle file uploads
            $this->processAttachments($rma->getRmaId());

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

    private function processAttachments(int $rmaId): void
    {
        $files = $_FILES['attachments'] ?? [];
        if (empty($files['name'])) {
            return;
        }

        $mediaDir  = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $uploadDir = 'kkkonrad/rma/' . $rmaId;

        foreach ($files['name'] as $index => $fileName) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext         = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed     = $this->config->getAllowedExtensions();
            $maxSize     = $this->config->getMaxFileSizeMb() * 1024 * 1024;

            // Server-side MIME type verification using finfo
            $finfo    = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['tmp_name'][$index]) ?: 'application/octet-stream';

            $allowedMimes = [
                'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png'  => 'image/png',  'gif'  => 'image/gif',
                'webp' => 'image/webp', 'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'zip'  => 'application/zip',
            ];

            $expectedMime = $allowedMimes[$ext] ?? null;
            if (!in_array($ext, $allowed, true)
                || $files['size'][$index] > $maxSize
                || ($expectedMime !== null && $mimeType !== $expectedMime)
            ) {
                continue;
            }

            $safeFileName = $this->random->getUniqueHash() . '.' . $ext;
            $relativePath = $uploadDir . '/' . $safeFileName;

            $mediaDir->create($uploadDir);
            $mediaDir->copyFile($files['tmp_name'][$index], $relativePath);

            /** @var \Kkkonrad\Rma\Model\RmaAttachment $attachment */
            $attachment = $this->rmaAttachmentFactory->create();
            $attachment->setRmaId($rmaId)
                ->setFilePath($relativePath)
                ->setFileName($fileName)
                ->setMimeType($files['type'][$index] ?? 'application/octet-stream')
                ->setFileSize($files['size'][$index]);

            $this->rmaAttachmentResource->save($attachment);
        }
    }
}
