<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Test\Unit\Model;

use Kkkonrad\Rma\Api\Data\RmaInterface;
use Kkkonrad\Rma\Api\RmaRepositoryInterface;
use Kkkonrad\Rma\Model\Config;
use Kkkonrad\Rma\Model\Rma;
use Kkkonrad\Rma\Model\RmaAttachment;
use Kkkonrad\Rma\Model\RmaAttachmentFactory;
use Kkkonrad\Rma\Model\RmaFactory;
use Kkkonrad\Rma\Model\RmaItem;
use Kkkonrad\Rma\Model\RmaItemFactory;
use Kkkonrad\Rma\Model\RmaManagement;
use Kkkonrad\Rma\Model\RmaMessage;
use Kkkonrad\Rma\Model\RmaMessageFactory;
use Kkkonrad\Rma\Model\RmaStatusHistory;
use Kkkonrad\Rma\Model\RmaStatusHistoryFactory;
use Kkkonrad\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaMessage as RmaMessageResource;
use Kkkonrad\Rma\Model\ResourceModel\RmaStatusHistory as RmaStatusHistoryResource;
use Kkkonrad\Rma\Model\StatusValidator;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Kkkonrad\Rma\Model\RmaManagement
 */
class RmaManagementTest extends TestCase
{
    private RmaManagement $rmaManagement;

    private RmaRepositoryInterface&MockObject $rmaRepository;
    private RmaItemResource&MockObject $rmaItemResource;
    private RmaMessageResource&MockObject $rmaMessageResource;
    private RmaStatusHistoryResource&MockObject $rmaStatusHistoryResource;
    private StatusValidator&MockObject $statusValidator;
    private Config&MockObject $config;
    private RmaFactory&MockObject $rmaFactory;
    private RmaItemFactory&MockObject $rmaItemFactory;
    private RmaMessageFactory&MockObject $rmaMessageFactory;
    private RmaStatusHistoryFactory&MockObject $rmaStatusHistoryFactory;
    private OrderRepositoryInterface&MockObject $orderRepository;
    private CreditmemoManagementInterface&MockObject $creditmemoManagement;
    private CreditmemoFactory&MockObject $creditmemoFactory;
    private EventManagerInterface&MockObject $eventManager;
    private LoggerInterface&MockObject $logger;
    private \Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory&MockObject $rmaItemCollectionFactory;
    private \Magento\Framework\Stdlib\DateTime\DateTime&MockObject $dateTime;
    private \Kkkonrad\Rma\Model\ResourceModel\RmaAddress\CollectionFactory&MockObject $rmaAddressCollectionFactory;
    private \Magento\Catalog\Api\ProductRepositoryInterface&MockObject $productRepository;
    private \Kkkonrad\Rma\Model\RmaPolicyFactory&MockObject $policyFactory;
    private \Kkkonrad\Rma\Model\ResourceModel\RmaPolicy&MockObject $policyResource;
    private \Magento\Framework\App\ResourceConnection&MockObject $resourceConnection;
    private \Kkkonrad\Rma\Model\RmaReasonFactory&MockObject $rmaReasonFactory;
    private \Kkkonrad\Rma\Model\ResourceModel\RmaReason&MockObject $rmaReasonResource;
    private \Kkkonrad\Rma\Model\RmaConditionFactory&MockObject $rmaConditionFactory;
    private \Kkkonrad\Rma\Model\ResourceModel\RmaCondition&MockObject $rmaConditionResource;
    private AdapterInterface&MockObject $connection;
    private \Kkkonrad\Rma\Model\InvoiceDateProvider&MockObject $invoiceDateProvider;
    private \Magento\Framework\Lock\LockManagerInterface&MockObject $lockManager;


    protected function setUp(): void
    {
        $this->rmaRepository        = $this->createMock(RmaRepositoryInterface::class);
        $this->rmaItemResource      = $this->createMock(RmaItemResource::class);
        $this->rmaMessageResource   = $this->createMock(RmaMessageResource::class);
        $this->rmaStatusHistoryResource = $this->createMock(RmaStatusHistoryResource::class);
        $this->statusValidator      = $this->createMock(StatusValidator::class);
        $this->config               = $this->createMock(Config::class);
        $this->rmaFactory           = $this->createMock(RmaFactory::class);
        $this->rmaItemFactory       = $this->createMock(RmaItemFactory::class);
        $this->rmaMessageFactory    = $this->createMock(RmaMessageFactory::class);
        $this->rmaStatusHistoryFactory = $this->createMock(RmaStatusHistoryFactory::class);
        $this->orderRepository      = $this->createMock(OrderRepositoryInterface::class);
        $this->creditmemoManagement = $this->createMock(CreditmemoManagementInterface::class);
        $this->creditmemoFactory    = $this->createMock(CreditmemoFactory::class);
        $this->eventManager         = $this->createMock(EventManagerInterface::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->rmaItemCollectionFactory = $this->createMock(\Kkkonrad\Rma\Model\ResourceModel\RmaItem\CollectionFactory::class);
        $this->dateTime             = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->rmaAddressCollectionFactory = $this->createMock(\Kkkonrad\Rma\Model\ResourceModel\RmaAddress\CollectionFactory::class);
        $this->productRepository    = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->policyFactory        = $this->createMock(\Kkkonrad\Rma\Model\RmaPolicyFactory::class);
        $this->policyResource       = $this->createMock(\Kkkonrad\Rma\Model\ResourceModel\RmaPolicy::class);
        $this->resourceConnection   = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->connection           = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->rmaReasonFactory      = $this->createMock(\Kkkonrad\Rma\Model\RmaReasonFactory::class);
        $this->rmaReasonResource     = $this->createMock(\Kkkonrad\Rma\Model\ResourceModel\RmaReason::class);
        $this->rmaConditionFactory   = $this->createMock(\Kkkonrad\Rma\Model\RmaConditionFactory::class);
        $this->rmaConditionResource  = $this->createMock(\Kkkonrad\Rma\Model\ResourceModel\RmaCondition::class);
        $this->invoiceDateProvider = $this->createMock(\Kkkonrad\Rma\Model\InvoiceDateProvider::class);
        $this->lockManager = $this->createMock(\Magento\Framework\Lock\LockManagerInterface::class);
        $this->lockManager->method('lock')->willReturn(true);

        $this->rmaManagement = new RmaManagement(
            $this->rmaRepository,
            $this->rmaItemResource,
            $this->rmaItemCollectionFactory,
            $this->rmaMessageResource,
            $this->rmaStatusHistoryResource,
            $this->statusValidator,
            $this->config,
            $this->rmaFactory,
            $this->rmaItemFactory,
            $this->rmaMessageFactory,
            $this->rmaStatusHistoryFactory,
            $this->orderRepository,
            $this->creditmemoManagement,
            $this->creditmemoFactory,
            $this->eventManager,
            $this->dateTime,
            $this->logger,
            $this->rmaAddressCollectionFactory,
            $this->productRepository,
            $this->policyFactory,
            $this->policyResource,
            $this->resourceConnection,
            $this->rmaReasonFactory,
            $this->rmaReasonResource,
            $this->rmaConditionFactory,
            $this->rmaConditionResource,
            $this->invoiceDateProvider,
            $this->lockManager
        );
    }

    public function testChangeStatusDelegatesToStatusValidator(): void
    {
        $rma = $this->createMock(Rma::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_NEW);
        $rma->method('getRmaId')->willReturn(1);
        $rma->method('setStatus')->willReturnSelf();

        $this->rmaRepository->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($rma);

        // StatusValidator::validate must be called
        $this->statusValidator->expects($this->once())
            ->method('validate')
            ->with(RmaInterface::STATUS_NEW, RmaInterface::STATUS_PENDING_REVIEW);

        $this->rmaRepository->expects($this->once())
            ->method('save')
            ->with($rma);

        $history = $this->createMock(RmaStatusHistory::class);
        $history->method('setRmaId')->willReturnSelf();
        $history->method('setStatusFrom')->willReturnSelf();
        $history->method('setStatusTo')->willReturnSelf();
        $history->method('setComment')->willReturnSelf();
        $history->method('setCreatedBy')->willReturnSelf();
        $history->method('setCreatedById')->willReturnSelf();
        $this->rmaStatusHistoryFactory->method('create')->willReturn($history);

        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('kkkonrad_rma_status_changed', $this->isType('array'));

        $result = $this->rmaManagement->changeStatus(1, RmaInterface::STATUS_PENDING_REVIEW);
        $this->assertSame($rma, $result);
    }

    public function testChangeStatusPropagatesValidatorException(): void
    {
        $rma = $this->createMock(Rma::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_CLOSED);

        $this->rmaRepository->method('getById')->willReturn($rma);

        $this->statusValidator->method('validate')
            ->willThrowException(new LocalizedException(__('Cannot transition')));

        $this->expectException(LocalizedException::class);

        $this->rmaManagement->changeStatus(1, RmaInterface::STATUS_NEW);
    }

    public function testChangeStatusRollsBackWhenHistoryCannotBeSaved(): void
    {
        $rma = $this->createMock(Rma::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_NEW);
        $rma->method('setStatus')->willReturnSelf();
        $this->rmaRepository->method('getById')->willReturn($rma);

        $history = $this->createMock(RmaStatusHistory::class);
        $history->method('setRmaId')->willReturnSelf();
        $history->method('setStatusFrom')->willReturnSelf();
        $history->method('setStatusTo')->willReturnSelf();
        $history->method('setComment')->willReturnSelf();
        $history->method('setCreatedBy')->willReturnSelf();
        $history->method('setCreatedById')->willReturnSelf();
        $this->rmaStatusHistoryFactory->method('create')->willReturn($history);
        $this->rmaStatusHistoryResource->method('save')->willThrowException(new \RuntimeException('write failed'));

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->never())->method('commit');
        $this->connection->expects($this->once())->method('rollBack');
        $this->eventManager->expects($this->never())->method('dispatch');

        $this->expectException(\RuntimeException::class);
        $this->rmaManagement->changeStatus(1, RmaInterface::STATUS_PENDING_REVIEW);
    }

    public function testCreateFromOrderRejectsEmptyItemList(): void
    {
        $this->orderRepository->expects($this->never())->method('get');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('at least one item');

        $this->rmaManagement->createFromOrder(1, 1, RmaInterface::RESOLUTION_REFUND, []);
    }

    public function testAddMessageRejectsBlankMessage(): void
    {
        $this->rmaRepository->expects($this->never())->method('getById');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Message cannot be empty');

        $this->rmaManagement->addMessage(1, " \n\t ", 'customer');
    }

    public function testAddMessageRejectsInvalidAuthorType(): void
    {
        $this->rmaRepository->expects($this->never())->method('getById');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid message author type');

        $this->rmaManagement->addMessage(1, 'Hello', 'unknown');
    }

    public function testApproveDoesNotChangeStatusWhenCreditMemoCreationFails(): void
    {
        $rma = $this->createMock(Rma::class);
        $rma->method('getStatus')->willReturn(RmaInterface::STATUS_PENDING_REVIEW);
        $rma->method('getResolutionType')->willReturn(RmaInterface::RESOLUTION_REFUND);
        $rma->method('getOrderId')->willReturn(10);
        $rma->method('getRmaId')->willReturn(5);

        $this->rmaRepository->expects($this->once())->method('getById')->with(5)->willReturn($rma);
        $this->statusValidator->expects($this->once())->method('validate')->with(
            RmaInterface::STATUS_PENDING_REVIEW,
            RmaInterface::STATUS_APPROVED
        );
        $this->orderRepository->method('get')->with(10)->willReturn($this->createMock(Order::class));
        $this->rmaItemCollectionFactory->method('create')
            ->willThrowException(new \RuntimeException('collection failed'));
        $this->rmaRepository->expects($this->never())->method('save');
        $this->eventManager->expects($this->never())->method('dispatch');
        $this->lockManager->expects($this->once())->method('unlock')->with('kkkonrad_rma_status_5');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('was not approved');
        $this->rmaManagement->approve(5);
    }

    public function testIsOrderEligibleForRmaReturnsFalseForNonExistentOrder(): void
    {
        $this->orderRepository->method('get')
            ->willThrowException(new NoSuchEntityException());

        $result = $this->rmaManagement->isOrderEligibleForRma(999, 1);
        $this->assertFalse($result);
    }

    public function testIsOrderEligibleForRmaReturnsFalseIfCustomerDoesNotMatchOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(42);
        $order->method('getStatus')->willReturn('complete');

        $this->orderRepository->method('get')->willReturn($order);

        $result = $this->rmaManagement->isOrderEligibleForRma(1, 99); // 99 != 42
        $this->assertFalse($result);
    }

    public function testIsOrderEligibleForRmaReturnsFalseForNonCompleteOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(1);
        $order->method('getStatus')->willReturn('processing'); // Not complete

        $this->orderRepository->method('get')->willReturn($order);

        $result = $this->rmaManagement->isOrderEligibleForRma(1, 1);
        $this->assertFalse($result);
    }

    public function testIsOrderEligibleForRmaReturnsFalseIfOutsideReturnWindow(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(1);
        $order->method('getStatus')->willReturn('complete');
        $order->method('getUpdatedAt')->willReturn('2020-01-01 00:00:00'); // Old order
        $order->method('getStoreId')->willReturn(1);

        $this->orderRepository->method('get')->willReturn($order);
        $this->config->method('getReturnWindowDays')->willReturn(30);

        $result = $this->rmaManagement->isOrderEligibleForRma(1, 1);
        $this->assertFalse($result);
    }
}
