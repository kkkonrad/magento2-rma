<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Model\AttachmentUploader;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\ResourceModel\RmaAttachment as RmaAttachmentResource;
use Kkkonrad\Rma\Model\RmaAttachmentFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\TestCase;

class AttachmentUploaderTest extends TestCase
{
    public function testNormalizesLaminasMultipleFileFormat(): void
    {
        $files = [
            [
                'name' => 'first.png',
                'tmp_name' => '/tmp/first',
                'error' => UPLOAD_ERR_OK,
                'size' => 123,
            ],
            [
                'name' => 'second.pdf',
                'tmp_name' => '/tmp/second',
                'error' => UPLOAD_ERR_OK,
                'size' => 456,
            ],
        ];

        self::assertSame($files, $this->createUploader()->normalize($files));
    }

    public function testNormalizesNativeParallelMultipleFileFormat(): void
    {
        $files = [
            'name' => ['first.png', 'second.pdf'],
            'tmp_name' => ['/tmp/first', '/tmp/second'],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [123, 456],
        ];

        self::assertSame([
            [
                'name' => 'first.png',
                'tmp_name' => '/tmp/first',
                'error' => UPLOAD_ERR_OK,
                'size' => 123,
            ],
            [
                'name' => 'second.pdf',
                'tmp_name' => '/tmp/second',
                'error' => UPLOAD_ERR_OK,
                'size' => 456,
            ],
        ], $this->createUploader()->normalize($files));
    }

    private function createUploader(): AttachmentUploader
    {
        return new AttachmentUploader(
            $this->createMock(Config::class),
            $this->createMock(Filesystem::class),
            $this->createMock(FileDriver::class),
            $this->createMock(Random::class),
            $this->createMock(RmaAttachmentFactory::class),
            $this->createMock(RmaAttachmentResource::class)
        );
    }
}
