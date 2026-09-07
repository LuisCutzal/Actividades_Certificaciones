<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\AttendanceListTable;
use Application\Model\AttendanceList;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class AttendanceListTableTest extends TestCase
{
    private AttendanceListTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new AttendanceListTable($this->tableGateway);
    }

    //insert

    public function testInsertCallsTableGatewayInsert(): void
    {
        $data = [
            'id' => 1,
            'id_actividad' => 1,
            'id_usuario' => 5,
            'estado' => 0,
            'creado' => "Personal Administrativo",
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);
        //luego de insertar retorna el valor
        $this->tableGateway
            ->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn(1);

        $result = $this->table->insert($data);

        $this->assertEquals(1, $result);
    }

    //getById

    public function testGetByIdReturnsAttendanceListWhenRowExists(): void
    {
        $row = [
            'id' => 1,
            'id_actividad' => 10,
            'id_usuario' => 5,
            'estado' => 1,
            'creado' => '2025-01-01 10:00:00',
            'aprobado' => null,
            'rechazado' => null,
        ];

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1]) //validamos que se haga el select correcto
            ->willReturn($resultSet);

        $result = $this->table->getById(1);

        $this->assertInstanceOf(AttendanceList::class, $result); //retorne el tipo correcto

        $this->assertEquals(1, $result->id);
        $this->assertEquals(10, $result->id_actividad);
        $this->assertEquals(5, $result->id_usuario);
        $this->assertEquals(1, $result->estado);
    }
    //no existe el registro

    public function testGetByIdReturnsNullWhenRowDoesNotExist(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        $result = $this->table->getById(1);

        $this->assertNull($result);
    }

    //updateStatus

    public function testUpdateStatusCallsTableGatewayUpdate(): void
    {
        $id = 1;
        $estado = 2;

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['estado' => $estado],
                ['id' => $id]
            );

        $this->table->updateStatus($id, $estado);
    }

    //getByActivity

    public function testGetByActivityReturnsArray(): void
    {
        $idActividad = 10;

        $rows = [
            [
                'id' => 1,
                'id_actividad' => 10,
                'id_usuario' => 5,
                'estado' => 1,
            ],
            [
                'id' => 2,
                'id_actividad' => 10,
                'id_usuario' => 8,
                'estado' => 0,
            ],
        ];

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($rows);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id_actividad' => $idActividad])
            ->willReturn($resultSet);

        $result = $this->table->getByActivity($idActividad);

        $this->assertEquals($rows, $result);
    }

    //update

    public function testUpdateCallsTableGatewayUpdate(): void
    {
        $data = [
            'estado' => 1,
            'aprobado' => '2025-01-01 10:00:00',
        ];

        $where = [
            'id' => 5,
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with($data, $where);

        $this->table->update($data, $where);
    }

    //delete

    public function testDeleteCallsTableGatewayDelete(): void
    {
        $where = [
            'id' => 1,
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('delete')
            ->with($where);

        $this->table->delete($where);
    }

    public function testDeleteReturnsDeleteResult(): void
    {
        $where = [
            'id' => 1,
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('delete')
            ->with($where)
            ->willReturn(1);

        $result = $this->table->delete($where);

        $this->assertEquals(1, $result);
    }

    //find
    public function testFindCallsGetById(): void
    {
        $attendanceList = new \Application\Model\AttendanceList();

        $table = $this->getMockBuilder(AttendanceListTable::class)
            ->setConstructorArgs([$this->tableGateway])
            ->onlyMethods(['getById'])
            ->getMock();

        $table->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($attendanceList);

        $result = $table->find(1);

        $this->assertSame($attendanceList, $result);
    }

    public function testFetchByActivityReturnsAttendanceList(): void
    {
        $row = [
            'id' => 1,
            'id_actividad' => 10,
            'id_usuario' => 5,
            'estado' => 1,
        ];

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($row);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id_actividad' => 10])
            ->willReturn($resultSet);

        $result = $this->table->fetchByActivity(10);

        $this->assertInstanceOf(\Application\Model\AttendanceList::class, $result);

        $this->assertEquals(1, $result->id);
        $this->assertEquals(10, $result->id_actividad);
    }

    public function testFetchByActivityReturnsNullWhenNoRowExists(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id_actividad' => 10])
            ->willReturn($resultSet);

        $result = $this->table->fetchByActivity(10);

        $this->assertNull($result);
    }

    public function testFetchByActividadReturnsArray(): void
    {
        $row = [
            'id' => 1,
            'id_actividad' => 10,
            'id_usuario' => 5,
        ];

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($row);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id_actividad' => 10])
            ->willReturn($resultSet);

        $result = $this->table->fetchByActividad(10);

        $this->assertEquals($row, $result);
    }

    public function testFetchByActividadReturnsNull(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id_actividad' => 10])
            ->willReturn($resultSet);

        $result = $this->table->fetchByActividad(10);

        $this->assertNull($result);
    }

    public function testRejectByActividadCallsUpdate(): void
    {
        $idActividad = 10;

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {
                    return $data['estado'] === 2
                        && isset($data['rechazado']);
                }),
                ['id_actividad' => $idActividad]
            );

        $this->table->rejectByActividad($idActividad);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/AttendanceListTableTest.php

*/