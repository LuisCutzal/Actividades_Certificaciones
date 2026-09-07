<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\CarreraTable;
use Application\Model\Carrera;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use \Laminas\Db\Sql\Predicate\In;

class CarreraTableTest extends TestCase
{
    private CarreraTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new CarreraTable($this->tableGateway);
    }

    //fetchAll
    public function testFetchAllReturnsAllCarreras(): void
    {
        $resultSet = $this->createMock(ResultSet::class); //Creamos un mock

        $this->tableGateway
            ->expects($this->once())
            ->method('select') //iniciamos el metodo que queremos
            ->with()
            ->willReturn($resultSet);

        $result = $this->table->fetchAll(); //Ejecutamos el real

        $this->assertSame($resultSet, $result); //Verificamos que sea exactamente el mismo objeto
    }

    //getCarrera

    public function testGetCarreraReturnsCarrera(): void
    {
        $carrera = new Carrera(); //usamos el obj carrera porque eso retorna el metodo real
        $carrera->carrera = 1;
        $carrera->nombre = 'Arquitectura';

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn($carrera);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['carrera' => 1])
            ->willReturn($resultSet);

        $result = $this->table->getCarrera(1);

        $this->assertSame($carrera, $result);
    }

    public function testGetCarreraThrowsExceptionWhenCarreraNotFound(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet
            ->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['carrera' => 999])
            ->willReturn($resultSet);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Carrera no encontrada');

        $this->table->getCarrera(999);
    }

    //getCarrerasMap

    public function testGetCarrerasMapReturnsFormattedArray(): void
    {
        $carrera1 = new Carrera();
        $carrera1->carrera = 1;
        $carrera1->nombre = 'Arquitectura';

        $carrera2 = new Carrera();
        $carrera2->carrera = 3;
        $carrera2->nombre = 'Diseño grafico';

        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Carrera());
        $resultSet->initialize([
            [
                'carrera' => 1,
                'nombre' => 'Arquitectura',
            ],
            [
                'carrera' => 3,
                'nombre' => 'Diseño grafico',
            ],
        ]);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with()
            ->willReturn($resultSet);

        $result = $this->table->getCarrerasMap();

        $this->assertSame([
            1 => 'Arquitectura',
            3 => 'Diseño grafico',
        ], $result);
    }

    public function testGetCarrerasMapReturnsEmptyArrayWhenNoResults(): void
    {
        $resultSet = new ResultSet();

        $resultSet->setArrayObjectPrototype(new Carrera());

        $resultSet->initialize([]);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with()
            ->willReturn($resultSet);

        $result = $this->table->getCarrerasMap();

        $this->assertSame([], $result);
    }

    public function testFetchByIdsBuildsWhereInClause(): void
    {
        $capturedCallback = null;

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($callback) use (&$capturedCallback) {
                $capturedCallback = $callback;

                return is_callable($callback);
            }));

        $this->table->fetchByIds([1, 3, 5]);

        $this->assertNotNull($capturedCallback);

        $select = new Select('carreras');

        $capturedCallback($select);

        $where = $select->where;

        $predicates = $where->getPredicates();

        $this->assertCount(1, $predicates);

        $predicate = $predicates[0][1];

        $this->assertInstanceOf(
            In::class,
            $predicate
        );

        $this->assertSame('carrera', $predicate->getIdentifier());
        $this->assertSame([1, 3, 5], $predicate->getValueSet());
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/CarreraTableTest.php

*/
