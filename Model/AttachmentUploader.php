<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as RmaAttachmentResource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Math\Random;

class AttachmentUploader
{
    /** @var array<string, string[]> */
    private const ALLOWED_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'mp4' => ['video/mp4'],
        'doc' => ['application/msword'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'zip' => ['application/zip'],
    ];

    public function __construct(
        private readonly Config $config,
        private readonly Filesystem $filesystem,
        private readonly FileDriver $fileDriver,
        private readonly Random $random,
        private readonly RmaAttachmentFactory $attachmentFactory,
        private readonly RmaAttachmentResource $attachmentResource
    ) {
    }

    /**
     * Normalize Laminas' list format and the native parallel $_FILES format.
     *
     * @param array<mixed> $files
     * @return array<int, array{name: string, tmp_name: string, error: int, size: int}>
     */
    public function normalize(array $files): array
    {
        if ($files === []) {
            return [];
        }

        if (isset($files['name']) && is_array($files['name'])) {
            $normalized = [];
            foreach ($files['name'] as $index => $name) {
                $normalized[] = [
                    'name' => (string) $name,
                    'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
                    'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($files['size'][$index] ?? 0),
                ];
            }
            return $normalized;
        }

        if (isset($files['name'])) {
            $files = [$files];
        }

        $normalized = [];
        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }
            $normalized[] = [
                'name' => (string) ($file['name'] ?? ''),
                'tmp_name' => (string) ($file['tmp_name'] ?? ''),
                'error' => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($file['size'] ?? 0),
            ];
        }
        return $normalized;
    }

    /**
     * @param array<mixed> $files
     * @return array<int, array{name: string, tmp_name: string, error: int, size: int, extension: string, mime_type: string}>
     * @throws LocalizedException
     */
    public function validate(array $files): array
    {
        $allowedExtensions = $this->config->getAllowedExtensions();
        $maxSize = $this->config->getMaxFileSizeMb() * 1024 * 1024;
        $validated = [];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($this->normalize($files) as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file['error'] !== UPLOAD_ERR_OK || $file['tmp_name'] === '' || $file['size'] <= 0) {
                throw new LocalizedException(__('One or more attachments are invalid.'));
            }

            $fileName = basename(str_replace('\\', '/', $file['name']));
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $detectedMime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

            if ($fileName === ''
                || strlen($fileName) > 255
                || !in_array($extension, $allowedExtensions, true)
                || !isset(self::ALLOWED_MIME_TYPES[$extension])
                || !in_array($detectedMime, self::ALLOWED_MIME_TYPES[$extension], true)
                || $maxSize <= 0
                || $file['size'] > $maxSize
                || !is_uploaded_file($file['tmp_name'])
            ) {
                throw new LocalizedException(__('One or more attachments are invalid.'));
            }

            $file['name'] = $fileName;
            $file['extension'] = $extension;
            $file['mime_type'] = $detectedMime;
            $validated[] = $file;
        }

        return $validated;
    }

    /**
     * @param array<int, array{name: string, tmp_name: string, error: int, size: int, extension: string, mime_type: string}> $files
     */
    public function save(int $rmaId, array $files): void
    {
        if ($files === []) {
            return;
        }

        $storageDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $uploadDirectory = 'rma/attachments/' . $rmaId;
        $connection = $this->attachmentResource->getConnection();
        $writtenFiles = [];
        $connection->beginTransaction();

        try {
            $storageDirectory->create($uploadDirectory);
            foreach ($files as $file) {
                $safeFileName = $this->random->getUniqueHash() . '.' . $file['extension'];
                $relativePath = $uploadDirectory . '/' . $safeFileName;
                $absolutePath = $storageDirectory->getAbsolutePath($relativePath);

                $this->fileDriver->copy($file['tmp_name'], $absolutePath);
                $writtenFiles[] = $absolutePath;

                $attachment = $this->attachmentFactory->create();
                $attachment->setRmaId($rmaId)
                    ->setFilePath($relativePath)
                    ->setFileName($file['name'])
                    ->setMimeType($file['mime_type'])
                    ->setFileSize($file['size']);
                $this->attachmentResource->save($attachment);
            }
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            foreach ($writtenFiles as $absolutePath) {
                if ($this->fileDriver->isExists($absolutePath)) {
                    $this->fileDriver->deleteFile($absolutePath);
                }
            }
            throw $exception;
        }
    }

    public function deleteForRma(int $rmaId, ?string $shippingLabel = null): void
    {
        $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $attachmentDirectory = 'rma/attachments/' . $rmaId;
        if ($varDirectory->isExist($attachmentDirectory)) {
            $varDirectory->delete($attachmentDirectory);
        }

        if ($shippingLabel !== null && str_starts_with($shippingLabel, 'rma/labels/')) {
            if ($varDirectory->isExist($shippingLabel)) {
                $varDirectory->delete($shippingLabel);
            }
        }

        $legacyMediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $legacyDirectory = 'kkkonrad/rma/' . $rmaId;
        if ($legacyMediaDirectory->isExist($legacyDirectory)) {
            $legacyMediaDirectory->delete($legacyDirectory);
        }
        if ($shippingLabel !== null
            && str_starts_with($shippingLabel, 'kkkonrad/rma/')
            && $legacyMediaDirectory->isExist($shippingLabel)
        ) {
            $legacyMediaDirectory->delete($shippingLabel);
        }
    }
}
