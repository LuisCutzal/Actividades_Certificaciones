<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use Application\Model\AuditLogTable;

use PHPUnit\Framework\TestCase;

use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

class AuditLogTableTest extends TestCase
{
    private AuditLogTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new AuditLogTable($this->tableGateway);
    }

    //save

    public function testSaveCallsInsertWithProvidedData(): void
    {
        $data = [
            'entity_type' => 'user',
            'entity_id' => 1,
            'action' => 'created',
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);

        $this->table->save($data);
    }

    public function testSavePropagatesExceptionFromInsert(): void
    {
        $data = [
            'entity_type' => 'user',
            'entity_id' => 1,
            'action' => 'created',
        ];

        $this->tableGateway
            ->method('insert')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->table->save($data);
    }

    //fetchLogs
    public function testFetchLogsReturnsArray(): void
    {
        $select = $this->createMock(Select::class);
        $sql = $this->createMock(Sql::class);

        $resultSet = new ResultSet();
        $resultSet->initialize([
            ['id' => 1, 'action' => 'created'],
            ['id' => 2, 'action' => 'updated'],
        ]);

        $sql->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $select->expects($this->once())
            ->method('order')
            ->with('created_at DESC');

        $result = $this->table->fetchLogs();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    private function assertFetchLogsFilter(
        ?string $entityType,
        ?int $entityId,
        ?string $action,
        array $expectedWhere
    ): void {
        $select = $this->createMock(Select::class);
        $sql = $this->createMock(Sql::class);

        $resultSet = new ResultSet();
        $resultSet->initialize([]);

        $sql->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        $select->expects($this->once())
            ->method('where')
            ->with($expectedWhere);

        $select->expects($this->once())
            ->method('order')
            ->with('created_at DESC');

        $this->tableGateway
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $this->table->fetchLogs($entityType, $entityId, $action);
    }
    public function testFetchLogsFiltersByEntityType(): void
    {
        $this->assertFetchLogsFilter(
            'user',
            null,
            null,
            ['entity_type' => 'user']
        );
    }

    public function testFetchLogsFiltersByEntityId(): void
    {
        $this->assertFetchLogsFilter(
            null,
            10,
            null,
            ['entity_id' => 10]
        );
    }

    public function testFetchLogsFiltersByAction(): void
    {
        $this->assertFetchLogsFilter(
            null,
            null,
            'deleted',
            ['action' => 'deleted']
        );
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/AuditLogTableTest.php

*/