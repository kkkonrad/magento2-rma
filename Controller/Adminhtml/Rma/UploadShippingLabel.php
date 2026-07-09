<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;

class UploadShippingLabel extends Action
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_edit';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly Filesystem $filesystem,
        private readonly Random $random
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rmaId = (int) $this->getRequest()->getParam('rma_id');
        $delete = (bool) $this->getRequest()->getParam('delete');

        try {
            $rma = $this->rmaRepository->getById($rmaId);

            if ($delete) {
                $oldLabel = $rma->getShippingLabel();
                if ($oldLabel) {
                    $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                    if ($mediaDir->isExist($oldLabel)) {
                        $mediaDir->delete($oldLabel);
                    }
                    $rma->setShippingLabel(null);
                    $this->rmaRepository->save($rma);
                    $this->messageManager->addSuccessMessage(__('Shipping label has been deleted.'));
                }
                return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
            }

            $files = $_FILES['shipping_label'] ?? null;
            if (!$files || empty($files['name']) || $files['error'] !== UPLOAD_ERR_OK) {
                throw new LocalizedException(__('Please select a valid PDF file.'));
            }

            $ext = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                throw new LocalizedException(__('Only PDF files are allowed for shipping labels.'));
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['tmp_name']) ?: 'application/octet-stream';
            if ($mimeType !== 'application/pdf') {
                throw new LocalizedException(__('Invalid PDF file uploaded.'));
            }

            // Max 10MB for label
            if ($files['size'] > 10 * 1024 * 1024) {
                throw new LocalizedException(__('File size cannot exceed 10MB.'));
            }

            // Upload
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadDir = 'kkkonrad/rma/labels/' . $rmaId;
            $safeFileName = $this->random->getUniqueHash() . '.pdf';
            $relativePath = $uploadDir . '/' . $safeFileName;

            $mediaDir->create($uploadDir);
            $mediaDir->copyFile($files['tmp_name'], $relativePath);

            // Delete old one if exists
            $oldLabel = $rma->getShippingLabel();
            if ($oldLabel && $mediaDir->isExist($oldLabel)) {
                $mediaDir->delete($oldLabel);
            }

            $rma->setShippingLabel($relativePath);
            $this->rmaRepository->save($rma);

            $this->messageManager->addSuccessMessage(__('Shipping label has been uploaded successfully.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while uploading the shipping label.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }
}
