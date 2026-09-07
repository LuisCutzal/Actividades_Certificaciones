<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\ExtensionTable;
use Application\Model\Extension;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\TableGateway\TableGateway;


class ExtensionTableTest extends TestCase
{
    private ExtensionTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new ExtensionTable($this->tableGateway);
    }

    public function testGetByIdReturnsExtensionWhenRecordExists(): void
    {
        $id = 1001;

        $row = [
            'extension' => 1001,
            'nombre' => 'Extensión Demo',
        ];

        $resultSet = $this->createMock(ResultSetInterface::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['extension' => $id])
            ->willReturn($resultSet);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn($row);

        $result = $this->table->getById($id);

        $this->assertInstanceOf(Extension::class, $result);
    }

    public function testGetByIdReturnsNullWhenRecordDoesNotExist(): void
    {
        $id = 9999;

        $resultSet = $this->createMock(ResultSetInterface::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['extension' => $id])
            ->willReturn($resultSet);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $result = $this->table->getById($id);

        $this->assertNull($result);
    }

    public function testFetchAllReturnsResultSet(): void
    {
        $resultSet = $this->createMock(ResultSetInterface::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with()
            ->willReturn($resultSet);

        $result = $this->table->fetchAll();

        $this->assertSame($resultSet, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/ExtensionTableTest.php

*/