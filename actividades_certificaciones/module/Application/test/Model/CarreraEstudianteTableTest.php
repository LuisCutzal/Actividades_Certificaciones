<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use Application\Model\CarreraEstudianteTable;

use PHPUnit\Framework\TestCase;

use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use \Laminas\Db\Sql\Predicate\In;

class CarreraEstudianteTableTest extends TestCase
{
    private CarreraEstudianteTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new CarreraEstudianteTable($this->tableGateway);
    }

    //getCarrerasPorEstudiante

    public function testGetCarrerasPorEstudianteRetornaCarreras(): void
    {
        $carnet = 2020001;

        // Simula filas retornadas por DB
        $resultSet = new ResultSet();

        $resultSet->initialize([
            ['carrera' => '10'],
            ['carrera' => '20'],
        ]);

        // Mock de Sql
        $sqlMock = $this->createMock(Sql::class);

        // Select real
        $select = new Select();

        // getSql()
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        // sql->select()
        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $resultado = $this->table->getCarrerasPorEstudiante($carnet);

        $this->assertSame([10, 20], $resultado);
    }

    public function testGetCarrerasPorEstudianteRetornaArregloVacio(): void
    {
        $carnet = 2020001;

        // ResultSet vacío
        $resultSet = new ResultSet();

        $resultSet->initialize([]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $resultado = $this->table->getCarrerasPorEstudiante($carnet);

        $this->assertSame([], $resultado);
    }

    public function testGetCarrerasPorEstudianteFiltraPorCarnet(): void
    {
        $carnet = 2020001;

        $resultSet = new ResultSet();

        $resultSet->initialize([]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function (Select $select) use ($carnet) {

                $where = $select->where;

                $predicates = $where->getPredicates();

                foreach ($predicates as [, $expression]) {

                    if (
                        method_exists($expression, 'getLeft')
                        && method_exists($expression, 'getRight')
                    ) {

                        return $expression->getLeft() === 'carnet'
                            && $expression->getRight() === $carnet;
                    }
                }

                return false;
            }))
            ->willReturn($resultSet);

        $this->table->getCarrerasPorEstudiante($carnet);
    }

    public function testGetCarrerasPorEstudianteSeleccionaSoloColumnaCarrera(): void
    {
        $carnet = 2020001;

        $resultSet = new ResultSet();

        $resultSet->initialize([]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function (Select $select) {

                $columns = $select->getRawState(Select::COLUMNS);

                return in_array('carrera', $columns, true)
                    && count($columns) === 1;
            }))
            ->willReturn($resultSet);

        $this->table->getCarrerasPorEstudiante($carnet);

        $this->assertTrue(true);
    }

    //getCarnetsPorCarrera

    public function testGetCarnetsPorCarreraRetornaVacioSiCarrerasEstaVacio(): void
    {
        $this->tableGateway
            ->expects($this->never())
            ->method('getSql');

        $this->tableGateway
            ->expects($this->never())
            ->method('selectWith');

        $resultado = $this->table->getCarnetsPorCarrera([]);

        $this->assertSame([], $resultado);
    }

    public function testGetCarnetsPorCarreraRetornaCarnetsUnicos(): void
    {
        $carreras = [10, 20];

        $resultSet = new ResultSet();

        $resultSet->initialize([
            ['carnet' => '100'],
            ['carnet' => '100'],
            ['carnet' => '200'],
        ]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $resultado = $this->table->getCarnetsPorCarrera($carreras);

        $this->assertSame([100, 200], $resultado);
    }

    public function testGetCarnetsPorCarreraAplicaClausulaInCorrectamente(): void
    {
        $carreras = [10, 20, 30];

        $resultSet = new ResultSet();

        $resultSet->initialize([]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function (Select $select) use ($carreras) {

                $where = $select->where;

                $predicates = $where->getPredicates();

                foreach ($predicates as [, $expression]) {

                    if ($expression instanceof In) {

                        return $expression->getIdentifier() === 'carrera'
                            && $expression->getValueSet() === $carreras;
                    }
                }

                return false;
            }))
            ->willReturn($resultSet);

        $this->table->getCarnetsPorCarrera($carreras);

        $this->assertTrue(true);
    }

    public function testGetCarnetsPorCarreraRetornaArregloVacioCuandoNoHayResultados(): void
    {
        $carreras = [10, 20];

        $resultSet = new ResultSet();

        $resultSet->initialize([]);

        $sqlMock = $this->createMock(Sql::class);

        $select = new Select();

        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sqlMock);

        $sqlMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($select);

        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($select)
            ->willReturn($resultSet);

        $resultado = $this->table->getCarnetsPorCarrera($carreras);

        $this->assertSame([], $resultado);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/CarreraEstudianteTableTest.php

*/