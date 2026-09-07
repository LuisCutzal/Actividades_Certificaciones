<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\ActivityTable;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

use Application\Model\Activity;
use Laminas\Db\ResultSet\ResultSet;
use ArrayObject;

class ActivityTableTest extends TestCase
{
    private ActivityTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new ActivityTable($this->tableGateway);
    }

    //getActivityById

    public function testGetActivityByIdReturnsActivity(): void
    {
        $row = new ArrayObject([
            'id' => 1,
            'nombre' => 'Actividad Test',
        ]);

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        $activity = $this->table->getActivityById(1);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );

        $this->assertEquals(1, $activity->id);
        $this->assertEquals('Actividad Test', $activity->nombre);
    }

    public function testGetActivityByIdReturnsNull(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 999])
            ->willReturn($resultSet);

        $activity = $this->table->getActivityById(999);

        $this->assertNull($activity);
    }

    //saveActivity

    public function testSaveActivityInsertsNewActivity(): void
    {
        $activity = new Activity();

        $activity->id = null;
        $activity->nombre = 'Nueva actividad';

        $this->tableGateway
            ->expects($this->once())
            ->method('insert');

        $this->tableGateway
            ->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn(10);

        $id = $this->table->saveActivity($activity);

        $this->assertEquals(10, $id);
    }

    public function testSaveActivityUpdatesExistingActivity(): void
    {
        $activity = new Activity();

        $activity->id = 5;
        $activity->nombre = 'Editada';

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->isType('array'),
                ['id' => 5]
            );

        $id = $this->table->saveActivity($activity);

        $this->assertEquals(5, $id);
    }

    //updateActivity

    public function testUpdateActivityApprovesActivity(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {

                    return $data['estado'] === 1
                        && $data['aprobado_por'] === 99
                        && isset($data['fecha_resolucion']);
                }),
                ['id' => 5]
            );

        $this->table->updateActivity(5, 99);
    }

    //rechazoActivity

    public function testRechazoActivityUpdatesCorrectly(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {

                    return $data['estado'] === 2
                        && $data['motivo_rechazo'] === 'Incorrecto'
                        && $data['rechazado_por'] === 7;
                }),
                ['id' => 1]
            );

        $this->table->rechazoActivity(
            1,
            'Incorrecto',
            7
        );
    }

    public function testBuscarActividadesCallsSelect(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturn($resultSet);

        $result = $this->table->buscarActividades('test');

        $this->assertSame($resultSet, $result);
    }

    private function createActivity(array $data = []): Activity
    {
        $activity = new Activity();

        $activity->id = $data['id'] ?? 1;
        $activity->nombre = $data['nombre'] ?? 'Actividad Test';
        $activity->organizador = $data['organizador'] ?? "Personal administrativo";
        $activity->fecha = $data['fecha'] ?? '2025-01-01';
        $activity->credito = $data['credito'] ?? 2;
        $activity->carrera = $data['carrera'] ?? 1;

        return $activity;
    }

    //editactivity

    public function testEditActivityUpdatesCorrectly(): void
    {
        $activity = $this->createActivity([
            'id' => 15,
            'nombre' => 'Actividad editada',
            'organizador' => 1,
            'fecha' => '2025-01-10',
            'credito' => 3,
            'carrera' => 1,
        ]);

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                [
                    'nombre' => 'Actividad editada',
                    'organizador' => 1,
                    'fecha' => '2025-01-10',
                    'credito' => 3,
                    'carrera' => 1,
                ],
                ['id' => 15]
            );

        $result = $this->table->editActivity($activity);

        $this->assertEquals(15, $result);
    }

    //getActivityByCredit

    public function testGetActivityByCreditReturnsActivity(): void
    {
        $row = new ArrayObject([
            'id' => 5,
            'credito' => 100,
            'nombre' => 'Actividad Credito'
        ]);

        $resultSet = $this->createMock(
            ResultSet::class
        );

        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'credito' => 100
            ])
            ->willReturn($resultSet);

        $activity = $this->table->getActivityByCredit(100);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );

        $this->assertEquals(
            5,
            $activity->id
        );

        $this->assertEquals(
            100,
            $activity->credito
        );
    }

    //find

    public function testFindReturnsActivity(): void
    {
        $row = new ArrayObject([
            'id' => 8,
            'nombre' => 'Actividad Find'
        ]);

        $resultSet = $this->createMock(
            ResultSet::class
        );

        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'id' => 8
            ])
            ->willReturn($resultSet);

        $activity = $this->table->find(8);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );

        $this->assertEquals(
            8,
            $activity->id
        );
    }

    //fetchAllPorRoles()

    public function testFetchAllPorRolesReturnsEmptyArrayWhenTiposIsEmpty(): void
    {
        $result = $this->table->fetchAllPorRoles(
            [],
            2,
            3,
            [1, 2],
            1
        );

        $this->assertEquals([], $result);
    }

    //valida el flujo principal de fetchAllPorRoles

    public function testFetchAllPorRolesCallsSelectWith(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->fetchAllPorRoles(
            [2, 3],
            2,
            5,
            [1],
            1
        );

        $this->assertSame($resultSet, $result);
    }

    //getActividadesPorEstado

    //tipo2
    public function testGetActividadesPorEstadoForTipo2(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->mockSelectWith($resultSet);

        $result = $this->table->getActividadesPorEstado(
            0, //estado
            2, //tipoid
            [1, 3], //carreras
            1, //extension
            '' //buscar
        );

        $this->assertSame($resultSet, $result);
    }

    //tipo3
    public function testGetActividadesPorEstadoForTipo3(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->mockSelectWith($resultSet);

        $result = $this->table->getActividadesPorEstado(
            1, //estado
            3, //tipoid
            [3], //carreras
            1, //extension
            '' //buscar
        );

        $this->assertSame($resultSet, $result);
    }

    //tipo4
    public function testGetActividadesPorEstadoForTipo4(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->mockSelectWith($resultSet);

        $result = $this->table->getActividadesPorEstado(
            1, //estado
            4, //tipoid
            [1], //carreras
            2, //extension
            '' //buscar
        );

        $this->assertSame($resultSet, $result);
    }

    //default
    public function testGetActividadesPorEstadoWithInvalidTipo(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->mockSelectWith($resultSet);

        $result = $this->table->getActividadesPorEstado(
            1,
            99,
            [],
            1,
            ''
        );

        $this->assertSame($resultSet, $result);
    }

    private function mockSelectWith(ResultSet $resultSet): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);
    }

    //searchActivity

    //busqueda de texto

    public function testSearchActivityWithTextQuery(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->searchActivity(
            'Actividad',
            [2, 3],
            2,
            5,
            [1],
            1
        );

        $this->assertSame($resultSet, $result);
    }

    //busqueda numerica
    public function testSearchActivityWithNumericQuery(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->searchActivity(
            '100',
            [2],
            2,
            5,
            [1],
            1
        );

        $this->assertSame($resultSet, $result);
    }

    //getActividadesPendientesPorTipos

    //admin
    public function testGetActividadesPendientesPorTiposForAdmin(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->getActividadesPendientesPorTipos(
            [1, 2, 3],
            1,
            10,
            [],
            1
        );

        $this->assertSame($resultSet, $result);
    }

    //otros usuarios
    public function testGetActividadesPendientesPorTiposForUser(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->getActividadesPendientesPorTipos(
            [2],
            2,
            5,
            [1],
            1
        );

        $this->assertSame($resultSet, $result);
    }

    //getActividadesPorEstadoYTipos
    public function testGetActividadesPorEstadoYTiposReturnsEmptyArray(): void
    {
        $result = $this->table->getActividadesPorEstadoYTipos(
            1,
            [],
            2,
            5,
            [1],
            1
        );

        $this->assertEquals([], $result);
    }

    public function testGetActividadesPorEstadoYTiposCallsSelectWith(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway
            ->expects($this->once())
            ->method('getTable')
            ->willReturn('actividad');

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with(
                $this->isInstanceOf(Select::class)
            )
            ->willReturn($resultSet);

        $result = $this->table->getActividadesPorEstadoYTipos(
            1,
            [2, 3],
            2,
            5,
            [1],
            1,
            'actividad'
        );

        $this->assertSame($resultSet, $result);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/ActivityTableTest.php

*/
