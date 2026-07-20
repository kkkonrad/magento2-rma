<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Controller\Adminhtml\Rma;

use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Kkkonrad\Rma\Model\Config;

class UploadShippingLabel extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Kkkonrad_Rma::rma_upload_label';

    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly Filesystem $filesystem,
        private readonly Random $random,
        private readonly Config $config,
        private readonly EventManagerInterface $eventManager,
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $rmaId   = (int) $request->getParam('rma_id');
        $delete  = (bool) $request->getParam('delete');

        try {
            $rma = $this->rmaRepository->getById($rmaId);

            if ($delete) {
                $oldLabel = $rma->getShippingLabel();
                if ($oldLabel) {
                    $this->deleteStoredFile((string) $oldLabel);
                    $rma->setShippingLabel(null);
                    $this->rmaRepository->save($rma);
                    $this->messageManager->addSuccessMessage(__('Shipping label has been deleted.'));
                }
                return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
            }

            // Fix 1: Use $request->getFiles() instead of $_FILES superglobal
            $files = $request->getFiles('shipping_label') ?: null;
            if (!$files || empty($files['name']) || $files['error'] !== UPLOAD_ERR_OK) {
                throw new LocalizedException(__('Please select a valid PDF file.'));
            }

            $ext = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                throw new LocalizedException(__('Only PDF files are allowed for shipping labels.'));
            }

            $finfo    = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['tmp_name']) ?: 'application/octet-stream';
            if ($mimeType !== 'application/pdf') {
                throw new LocalizedException(__('Invalid PDF file uploaded.'));
            }

            // Fix 3: Use config value instead of hardcoded 10MB
            $maxSize = $this->config->getMaxFileSizeMb() * 1024 * 1024;
            if ($files['size'] > $maxSize) {
                throw new LocalizedException(
                    __('File size cannot exceed %1MB.', $this->config->getMaxFileSizeMb())
                );
            }

            // Upload
            $mediaDir     = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $uploadDir    = 'rma/labels/' . $rmaId;
            $safeFileName = $this->random->getUniqueHash() . '.pdf';
            $relativePath = $uploadDir . '/' . $safeFileName;

            $mediaDir->create($uploadDir);
            $mediaDir->getDriver()->copy(
                $files['tmp_name'],
                $mediaDir->getAbsolutePath($relativePath)
            );

            // Delete old label if it exists
            $oldLabel = $rma->getShippingLabel();
            if ($oldLabel) {
                $this->deleteStoredFile((string) $oldLabel);
            }

            $rma->setShippingLabel($relativePath);
            $this->rmaRepository->save($rma);

            // Fix 7: Dispatch event instead of sending email directly in controller
            $this->eventManager->dispatch('kkkonrad_rma_label_uploaded', ['rma' => $rma]);

            $this->messageManager->addSuccessMessage(__('Shipping label has been uploaded successfully.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while uploading the shipping label.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['rma_id' => $rmaId]);
    }

    private function deleteStoredFile(string $filePath): void
    {
        foreach ([DirectoryList::VAR_DIR, DirectoryList::MEDIA] as $directoryCode) {
            $directory = $this->filesystem->getDirectoryWrite($directoryCode);
            if ($directory->isExist($filePath)) {
                $directory->delete($filePath);
                return;
            }
        }
    }
}
