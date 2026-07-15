<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model\Source;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\Source\Status;
use Magento\Framework\Phrase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    #[DataProvider('statusLabels')]
    public function testReturnsTranslatablePhrase(string $status, string $label): void
    {
        $result = (new Status())->getLabel($status);

        self::assertInstanceOf(Phrase::class, $result);
        self::assertSame($label, (string)$result);
    }

    public static function statusLabels(): array
    {
        return [
            [RmaInterface::STATUS_NEW, 'Kkkonrad RMA status: new'],
            [RmaInterface::STATUS_PENDING_REVIEW, 'Kkkonrad RMA status: pending review'],
            [RmaInterface::STATUS_APPROVED, 'Kkkonrad RMA status: approved'],
            [RmaInterface::STATUS_REJECTED, 'Kkkonrad RMA status: rejected'],
            [RmaInterface::STATUS_ITEM_IN_TRANSIT, 'Kkkonrad RMA status: item in transit'],
            [RmaInterface::STATUS_ITEM_RECEIVED, 'Kkkonrad RMA status: item received'],
            [RmaInterface::STATUS_RESOLVED, 'Kkkonrad RMA status: resolved'],
            [RmaInterface::STATUS_CLOSED, 'Kkkonrad RMA status: closed'],
            [RmaInterface::STATUS_CANCELLED, 'Kkkonrad RMA status: cancelled'],
        ];
    }

    public function testReturnsUnknownStatusUnchanged(): void
    {
        self::assertSame('custom_status', (new Status())->getLabel('custom_status'));
    }
}
