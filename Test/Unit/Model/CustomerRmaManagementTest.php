<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Api\RmaManagementInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\CustomerRmaManagement;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AuthorizationException;
use PHPUnit\Framework\TestCase;

class CustomerRmaManagementTest extends TestCase
{
    public function testCreateUsesCustomerIdFromAuthenticatedContext(): void
    {
        $context = $this->createMock(UserContextInterface::class);
        $context->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $context->method('getUserId')->willReturn(42);
        $repository = $this->createMock(RmaRepositoryInterface::class);
        $management = $this->createMock(RmaManagementInterface::class);
        $events = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $rma = $this->createMock(\Kkkonrad\Rma\Api\Data\RmaInterface::class);
        $rma->method('getRmaId')->willReturn(7);
        $management->expects($this->once())->method('createFromOrder')
            ->with(100, 42, 'refund', [], null, false, false, false)
            ->willReturn($rma);
        $management->expects($this->once())->method('changeStatus')
            ->with(7, 'pending_review', null, 'customer', 42)
            ->willReturn($rma);

        $events->expects($this->once())->method('dispatch')->with('kkkonrad_rma_created');
        (new CustomerRmaManagement($context, $repository, $management, $events))
            ->createFromOrder(100, 'refund', []);
    }

    public function testMessageChecksOwnershipBeforeWriting(): void
    {
        $context = $this->createMock(UserContextInterface::class);
        $context->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $context->method('getUserId')->willReturn(42);
        $repository = $this->createMock(RmaRepositoryInterface::class);
        $repository->expects($this->once())->method('getByIdForCustomer')->with(5, 42);
        $management = $this->createMock(RmaManagementInterface::class);
        $management->expects($this->once())->method('addMessage')
            ->with(5, 'Hello', 'customer', 42, null, false);

        (new CustomerRmaManagement(
            $context,
            $repository,
            $management,
            $this->createMock(\Magento\Framework\Event\ManagerInterface::class)
        ))->addMessage(5, 'Hello');
    }

    public function testGuestContextIsRejected(): void
    {
        $context = $this->createMock(UserContextInterface::class);
        $context->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_GUEST);
        $context->method('getUserId')->willReturn(null);

        $this->expectException(AuthorizationException::class);
        (new CustomerRmaManagement(
            $context,
            $this->createMock(RmaRepositoryInterface::class),
            $this->createMock(RmaManagementInterface::class),
            $this->createMock(\Magento\Framework\Event\ManagerInterface::class)
        ))->createFromOrder(100, 'refund', []);
    }
}
