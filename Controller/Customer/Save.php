<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Customer;

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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
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
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => __('Please log in to submit a return request.')]);
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

            // Handle file uploads
            $this->processAttachments($rma->getRmaId());

            return $result->setData([
                'success'      => true,
                'rma_id'       => $rma->getRmaId(),
                'redirect_url' => sprintf('%srma/index/view/rma_id/%d', '{{base_url}}', $rma->getRmaId()),
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

            if (!in_array($ext, $allowed, true) || $files['size'][$index] > $maxSize) {
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
