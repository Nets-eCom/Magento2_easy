<?php

declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Plugin;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;
use Nexi\Checkout\Model\Order\NexiOrderFields;
use Nexi\Checkout\Plugin\AddPaymentMethodToOrderGrid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddPaymentMethodToOrderGridTest extends TestCase
{
    private AddPaymentMethodToOrderGrid $plugin;

    /**
     * @var CollectionFactory|MockObject
     */
    private $subjectMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var AbstractDb|MockObject
     */
    private $resourceMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    protected function setUp(): void
    {
        $this->plugin = new AddPaymentMethodToOrderGrid();
        $this->subjectMock = $this->createMock(CollectionFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->resourceMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->createMock(Select::class);
    }

    public function testAfterGetReportReturnsCollectionUnchangedForWrongRequestName(): void
    {
        $this->collectionMock->expects($this->never())->method('getMainTable');
        $this->collectionMock->expects($this->never())->method('getSelect');

        $result = $this->plugin->afterGetReport(
            $this->subjectMock,
            $this->collectionMock,
            'wrong_request_name'
        );

        $this->assertSame($this->collectionMock, $result);
    }

    public function testAfterGetReportReturnsCollectionUnchangedForNonOrderGridCollection(): void
    {
        $otherCollection = $this->createMock(SearchResult::class);
        $otherCollection->expects($this->never())->method('getSelect');

        $result = $this->plugin->afterGetReport(
            $this->subjectMock,
            $otherCollection,
            'sales_order_grid_data_source'
        );

        $this->assertSame($otherCollection, $result);
    }

    public function testAfterGetReportReturnsCollectionUnchangedForWrongMainTable(): void
    {
        $this->collectionMock->method('getMainTable')->willReturn('some_other_table');
        $this->collectionMock->method('getResource')->willReturn($this->resourceMock);
        $this->resourceMock->method('getTable')->with('sales_order_grid')->willReturn('sales_order_grid');

        $this->collectionMock->expects($this->never())->method('getSelect');

        $result = $this->plugin->afterGetReport(
            $this->subjectMock,
            $this->collectionMock,
            'sales_order_grid_data_source'
        );

        $this->assertSame($this->collectionMock, $result);
    }

    public function testAfterGetReportAddsJoinAndFilterMap(): void
    {
        $salesOrderGridTable = 'sales_order_grid';
        $salesOrderTable = 'sales_order';

        $this->collectionMock->method('getMainTable')->willReturn($salesOrderGridTable);
        $this->collectionMock->method('getResource')->willReturn($this->resourceMock);
        $this->resourceMock->method('getTable')->willReturnMap([
            ['sales_order_grid', $salesOrderGridTable],
            ['sales_order', $salesOrderTable],
        ]);

        $this->collectionMock->method('getSelect')->willReturn($this->selectMock);

        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['so' => $salesOrderTable],
                'so.entity_id = main_table.entity_id',
                [NexiOrderFields::NEXI_PAYMENT_METHOD => 'so.' . NexiOrderFields::NEXI_PAYMENT_METHOD]
            );

        $this->collectionMock->expects($this->once())
            ->method('addFilterToMap')
            ->with(
                NexiOrderFields::NEXI_PAYMENT_METHOD,
                'so.' . NexiOrderFields::NEXI_PAYMENT_METHOD
            );

        $result = $this->plugin->afterGetReport(
            $this->subjectMock,
            $this->collectionMock,
            'sales_order_grid_data_source'
        );

        $this->assertSame($this->collectionMock, $result);
    }
}
