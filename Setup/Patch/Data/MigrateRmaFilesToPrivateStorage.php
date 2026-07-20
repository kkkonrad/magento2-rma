<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Setup\Patch\Data;

use Kkkonrad\Rma\Model\ResourceModel\Rma as RmaResource;
use Kkkonrad\Rma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as AttachmentResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment\CollectionFactory as AttachmentCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class MigrateRmaFilesToPrivateStorage implements DataPatchInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        private readonly AttachmentResource $attachmentResource,
        private readonly RmaCollectionFactory $rmaCollectionFactory,
        private readonly RmaResource $rmaResource,
        private readonly \Magento\Framework\Filesystem\Driver\File $fileDriver,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $private = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        foreach ($this->attachmentCollectionFactory->create() as $attachment) {
            $source = (string) $attachment->getFilePath();
            if (!str_starts_with($source, 'kkkonrad/rma/') || !$media->isExist($source)) {
                continue;
            }
            $target = 'rma/attachments/' . (int) $attachment->getRmaId() . '/' . basename($source);
            $this->migrateFile($media, $private, $source, $target, function () use ($attachment, $target): void {
                $attachment->setFilePath($target);
                $this->attachmentResource->save($attachment);
            });
        }

        foreach ($this->rmaCollectionFactory->create() as $rma) {
            $source = (string) $rma->getShippingLabel();
            if (!str_starts_with($source, 'kkkonrad/rma/') || !$media->isExist($source)) {
                continue;
            }
            $target = 'rma/labels/' . (int) $rma->getRmaId() . '/' . basename($source);
            $this->migrateFile($media, $private, $source, $target, function () use ($rma, $target): void {
                $rma->setShippingLabel($target);
                $this->rmaResource->save($rma);
            });
        }

        return $this;
    }

    private function migrateFile(
        \Magento\Framework\Filesystem\Directory\WriteInterface $sourceDirectory,
        \Magento\Framework\Filesystem\Directory\WriteInterface $targetDirectory,
        string $source,
        string $target,
        callable $persistPath
    ): void {
        try {
            $targetDirectory->create(dirname($target));
            $this->fileDriver->copy(
                $sourceDirectory->getAbsolutePath($source),
                $targetDirectory->getAbsolutePath($target)
            );
            $persistPath();
        } catch (\Throwable $exception) {
            if ($targetDirectory->isExist($target)) {
                $targetDirectory->delete($target);
            }
            $this->logger->error('Unable to migrate an RMA file to private storage.', [
                'source' => $source,
                'target' => $target,
                'exception' => $exception,
            ]);
            return;
        }

        try {
            $sourceDirectory->delete($source);
        } catch (\Throwable $exception) {
            $this->logger->warning('Migrated RMA file, but could not remove its public copy.', [
                'source' => $source,
                'exception' => $exception,
            ]);
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
