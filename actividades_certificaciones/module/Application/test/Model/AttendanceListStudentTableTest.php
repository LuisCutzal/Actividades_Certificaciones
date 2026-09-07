<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\AttendanceListStudentTable;

use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use ArrayObject;

class AttendanceListStudentTableTest extends TestCase
{
    private AttendanceListStudentTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new AttendanceListStudentTable($this->tableGateway, '');
    }

    //insert

    public function testInsertCallsTableGatewayInsert(): void
    {
        $data = [
            'id_attendance_list' => 1,
            'carnet' => 2020001,
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);

        $this->table->insert($data);
    }

    //getbyList

    public function testGetByListReturnsArray(): void
    {
        $idList = 1;

        $expectedData = [
            [
                'id_attendance_list' => 1,
                'carnet' => 2020001,
            ],
            [
                'id_attendance_list' => 1,
                'carnet' => 2020002,
            ],
        ];

        $resultSetMock = $this->createMock(ResultSet::class);

        $resultSetMock
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'id_attendance_list' => $idList
            ])
            ->willReturn($resultSetMock);

        $result = $this->table->getByList($idList);

        $this->assertEquals($expectedData, $result);
    }

    //delete

    public function testDeleteCallsTableGatewayDelete(): void
    {
        $where = [
            'id_attendance_list' => 1,
            'carnet' => 2020001,
        ];

        $expectedResult = 1;

        $this->tableGateway
            ->expects($this->once())
            ->method('delete')
            ->with($where)
            ->willReturn($expectedResult);

        $result = $this->table->delete($where);

        $this->assertEquals($expectedResult, $result);
    }

    //update

    public function testUpdateCallsTableGatewayUpdate(): void
    {
        $data = [
            'estado' => 1,
        ];

        $where = [
            'id_attendance_list' => 1,
            'carnet' => 2020001,
        ];

        $expectedResult = 1;

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with($data, $where)
            ->willReturn($expectedResult);

        $result = $this->table->update($data, $where);

        $this->assertEquals($expectedResult, $result);
    }

    //fetchByLista

    //existe registro

    public function testFetchByListaReturnsArrayWhenRecordExists(): void
    {
        $listaId = 1;

        $row = new ArrayObject([
            'id_attendance_list' => 1,
            'carnet' => 2020001,
        ]);

        $resultSetMock = $this->createMock(ResultSet::class);

        $resultSetMock
            ->expects($this->once())
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'id_attendance_list' => $listaId
            ])
            ->willReturn($resultSetMock);

        $result = $this->table->fetchByLista($listaId);

        $this->assertEquals((array) $row, $result);
    }

    //no existe registro

    public function testFetchByListaReturnsNullWhenRecordDoesNotExist(): void
    {
        $listaId = 1;

        $resultSetMock = $this->createMock(ResultSet::class);

        $resultSetMock
            ->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'id_attendance_list' => $listaId
            ])
            ->willReturn($resultSetMock);

        $result = $this->table->fetchByLista($listaId);

        $this->assertNull($result);
    }

    //getDetalleLista
    public function testGetDetalleListaConfiguresSelectCorrectly(): void
    {
        $listaId = 5;

        $expectedData = [
            ['carnet' => 2020001],
            ['carnet' => 2020002],
        ];

        $resultSetMock = $this->createMock(ResultSet::class);

        $resultSetMock
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedData);

        $capturedCallback = null;

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($callback) use (&$capturedCallback) {

                $capturedCallback = $callback;

                return is_callable($callback);
            }))
            ->willReturn($resultSetMock);

        $result = $this->table->getDetalleLista($listaId);

        // Verifica retorno
        $this->assertEquals($expectedData, $result);

        // Ejecutar callback REAL
        $select = new Select('attendance_list_student');

        $capturedCallback($select);

        // Verificar columns
        $this->assertEquals(
            ['carnet'],
            $select->getRawState(Select::COLUMNS)
        );

        // Verificar where
        $where = $select->getRawState(Select::WHERE);

        $predicates = $where->getPredicates();

        $this->assertCount(1, $predicates);

        $predicate = $predicates[0][1];

        $this->assertEquals(
            'id_attendance_list',
            $predicate->getLeft()
        );

        $this->assertEquals(
            $listaId,
            $predicate->getRight()
        );
    }

    //getCarnetsPorLista

    //sin busqueda
    public function testGetCarnetsPorListaWithoutSearch(): void
    {
        $listaId = 5;

        $expectedData = [
            ['carnet' => 2020001],
            ['carnet' => 2020002],
        ];

        $resultSet = new \ArrayIterator($expectedData);

        $capturedCallback = null;

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($callback) use (&$capturedCallback) {

                $capturedCallback = $callback;

                return is_callable($callback);
            }))
            ->willReturn($resultSet);

        $result = $this->table->getCarnetsPorLista($listaId);

        // Verificar retorno
        $this->assertEquals($expectedData, $result);

        // Ejecutar callback
        $select = new Select('attendance_list_student');

        $capturedCallback($select);

        // Verificar columns
        $this->assertEquals(
            ['carnet'],
            $select->getRawState(Select::COLUMNS)
        );

        // Verificar where
        $where = $select->getRawState(Select::WHERE);

        $predicates = $where->getPredicates();

        // Solo debe existir un predicate
        $this->assertCount(1, $predicates);

        $predicate = $predicates[0][1];

        $this->assertEquals(
            'id_attendance_list',
            $predicate->getLeft()
        );

        $this->assertEquals(
            $listaId,
            $predicate->getRight()
        );
    }

    //con busqueda
    public function testGetCarnetsPorLista(): void
    {
        $listaId = 5;

        $expectedData = [
            ['carnet' => 2020001],
        ];

        $resultSet = new \ArrayIterator($expectedData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturn($resultSet);

        $result = $this->table->getCarnetsPorLista($listaId);

        $this->assertEquals($expectedData, $result);
    }

    //getActividadesByStudent

    //sin busquda
    public function testGetActividadesByStudentWithoutSearch(): void
    {
        $idStudent = 2020001;

        $expectedData = [
            [
                'actividad_nombre' => 'Conferencia',
                'credito' => 3,
            ],
        ];

        $resultSet = new \ArrayIterator($expectedData);

        $capturedSelect = null;

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use (&$capturedSelect) {

                $capturedSelect = $select;

                return $select instanceof Select;
            }))
            ->willReturn($resultSet);

        $result = $this->table->getActividadesByStudent(
            $idStudent
        );

        // Verificar retorno
        $this->assertEquals($expectedData, $result);

        // Verificar que sí se construyó Select
        $this->assertInstanceOf(
            Select::class,
            $capturedSelect
        );

        // Verificar tabla principal
        $this->assertEquals(
            'attendance_list_student',
            $capturedSelect->getRawState(Select::TABLE)
        );
    }

    //con busqueda

    public function testGetActividadesByStudentWithSearch(): void
    {
        $idStudent = 2020001;
        $buscar = 'Conferencia';

        $expectedData = [
            [
                'actividad_nombre' => 'Conferencia de PHP',
                'credito' => 2,
            ],
        ];

        $resultSet = new \ArrayIterator($expectedData);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->isInstanceOf(Select::class))
            ->willReturn($resultSet);

        $result = $this->table->getActividadesByStudent(
            $idStudent,
            $buscar
        );

        $this->assertEquals($expectedData, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/AttendanceListStudentTableTest.php

*/