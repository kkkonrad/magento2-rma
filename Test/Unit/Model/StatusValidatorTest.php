<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Model\StatusValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kkkonrad\Rma\Model\StatusValidator
 */
class StatusValidatorTest extends TestCase
{
    private StatusValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StatusValidator();
    }

    /**
     * @dataProvider validTransitionsProvider
     */
    public function testValidTransitions(string $from, string $to): void
    {
        // Should not throw
        $this->validator->validate($from, $to);
        $this->assertTrue(true); // Explicit assertion
    }

    public static function validTransitionsProvider(): array
    {
        return [
            'new → pending_review'         => [RmaInterface::STATUS_NEW, RmaInterface::STATUS_PENDING_REVIEW],
            'new → cancelled'              => [RmaInterface::STATUS_NEW, RmaInterface::STATUS_CANCELLED],
            'pending_review → approved'    => [RmaInterface::STATUS_PENDING_REVIEW, RmaInterface::STATUS_APPROVED],
            'pending_review → rejected'    => [RmaInterface::STATUS_PENDING_REVIEW, RmaInterface::STATUS_REJECTED],
            'pending_review → cancelled'   => [RmaInterface::STATUS_PENDING_REVIEW, RmaInterface::STATUS_CANCELLED],
            'approved → item_in_transit'   => [RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_ITEM_IN_TRANSIT],
            'approved → cancelled'         => [RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_CANCELLED],
            'rejected → closed'            => [RmaInterface::STATUS_REJECTED, RmaInterface::STATUS_CLOSED],
            'item_in_transit → received'   => [RmaInterface::STATUS_ITEM_IN_TRANSIT, RmaInterface::STATUS_ITEM_RECEIVED],
            'item_received → resolved'     => [RmaInterface::STATUS_ITEM_RECEIVED, RmaInterface::STATUS_RESOLVED],
            'resolved → closed'            => [RmaInterface::STATUS_RESOLVED, RmaInterface::STATUS_CLOSED],
        ];
    }

    /**
     * @dataProvider invalidTransitionsProvider
     */
    public function testInvalidTransitionsThrowException(string $from, string $to): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validate($from, $to);
    }

    public static function invalidTransitionsProvider(): array
    {
        return [
            'new → approved'               => [RmaInterface::STATUS_NEW, RmaInterface::STATUS_APPROVED],
            'new → closed'                 => [RmaInterface::STATUS_NEW, RmaInterface::STATUS_CLOSED],
            'approved → new'               => [RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_NEW],
            'closed → any'                 => [RmaInterface::STATUS_CLOSED, RmaInterface::STATUS_NEW],
            'cancelled → any'              => [RmaInterface::STATUS_CANCELLED, RmaInterface::STATUS_APPROVED],
            'resolved → new'               => [RmaInterface::STATUS_RESOLVED, RmaInterface::STATUS_NEW],
            'item_in_transit → approved'   => [RmaInterface::STATUS_ITEM_IN_TRANSIT, RmaInterface::STATUS_APPROVED],
        ];
    }

    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validate(RmaInterface::STATUS_NEW, 'invalid_status');
    }

    public function testUnknownFromStatusThrowsException(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validate('unknown_status', RmaInterface::STATUS_APPROVED);
    }

    public function testIsTransitionAllowedReturnsTrueForValid(): void
    {
        $this->assertTrue(
            $this->validator->isTransitionAllowed(RmaInterface::STATUS_NEW, RmaInterface::STATUS_PENDING_REVIEW)
        );
    }

    public function testIsTransitionAllowedReturnsFalseForInvalid(): void
    {
        $this->assertFalse(
            $this->validator->isTransitionAllowed(RmaInterface::STATUS_CLOSED, RmaInterface::STATUS_NEW)
        );
    }

    public function testGetAllowedTransitions(): void
    {
        $allowed = $this->validator->getAllowedTransitions(RmaInterface::STATUS_PENDING_REVIEW);

        $this->assertContains(RmaInterface::STATUS_APPROVED, $allowed);
        $this->assertContains(RmaInterface::STATUS_REJECTED, $allowed);
        $this->assertContains(RmaInterface::STATUS_CANCELLED, $allowed);
        $this->assertNotContains(RmaInterface::STATUS_NEW, $allowed);
    }

    public function testIsTerminalStatusForClosed(): void
    {
        $this->assertTrue($this->validator->isTerminalStatus(RmaInterface::STATUS_CLOSED));
        $this->assertTrue($this->validator->isTerminalStatus(RmaInterface::STATUS_CANCELLED));
    }

    public function testIsNotTerminalStatusForPendingReview(): void
    {
        $this->assertFalse($this->validator->isTerminalStatus(RmaInterface::STATUS_PENDING_REVIEW));
    }

    public function testGetAllStatuses(): void
    {
        $statuses = $this->validator->getAllStatuses();

        $this->assertContains(RmaInterface::STATUS_NEW, $statuses);
        $this->assertContains(RmaInterface::STATUS_CLOSED, $statuses);
        $this->assertCount(9, $statuses);
    }
}
