<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as AttachmentResource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

class RmaFileDownloader
{
    public function __construct(
        private readonly RmaAttachmentFactory $attachmentFactory,
        private readonly AttachmentResource $attachmentResource,
        private readonly Filesystem $filesystem,
        private readonly FileFactory $fileFactory
    ) {
    }

    public function download(RmaInterface $rma, ?int $attachmentId = null): \Magento\Framework\App\ResponseInterface
    {
        if ($attachmentId !== null) {
            $attachment = $this->attachmentFactory->create();
            $this->attachmentResource->load($attachment, $attachmentId);
            if (!$attachment->getAttachmentId() || (int) $attachment->getRmaId() !== (int) $rma->getRmaId()) {
                throw new LocalizedException(__('The requested attachment does not exist.'));
            }
            return $this->createResponse(
                (string) $attachment->getFileName(),
                (string) $attachment->getFilePath(),
                (string) $attachment->getMimeType()
            );
        }

        $filePath = (string) $rma->getShippingLabel();
        if ($filePath === '') {
            throw new LocalizedException(__('The shipping label does not exist.'));
        }
        return $this->createResponse('return-label-' . $rma->getIncrementId() . '.pdf', $filePath, 'application/pdf');
    }

    private function createResponse(string $downloadName, string $filePath, string $mimeType): \Magento\Framework\App\ResponseInterface
    {
        foreach ([DirectoryList::VAR_DIR, DirectoryList::MEDIA] as $directoryCode) {
            $directory = $this->filesystem->getDirectoryRead($directoryCode);
            if ($directory->isExist($filePath)) {
                return $this->fileFactory->create(
                    $downloadName,
                    ['type' => 'filename', 'value' => $filePath, 'rm' => false],
                    $directoryCode,
                    $mimeType
                );
            }
        }
        throw new LocalizedException(__('The requested file is no longer available.'));
    }
}
