<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\InscripcionTable;
use Application\Model\Inscripcion;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;


class InscripcionTableTest extends TestCase
{
    private InscripcionTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new InscripcionTable($this->tableGateway);
    }

    //estudianteInscrito

    public function testGetByIdReturnsNullWhenNotFound(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['inscripcion' => 1])
            ->willReturn($resultSet);

        $result = $this->table->getById(1);

        $this->assertNull($result);
    }

    public function testGetByIdReturnsInscripcionWhenFound(): void
    {
        $rowData = [
            'inscripcion' => 1,
            'carnet' => 20240001,
            'carrera' => 10,
            'extension' => 1,
        ];

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($rowData);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['inscripcion' => 1])
            ->willReturn($resultSet);

        $result = $this->table->getById(1);

        $this->assertInstanceOf(Inscripcion::class, $result);

        $this->assertEquals(20240001, $result->carnet);
        $this->assertEquals(10, $result->carrera);
        $this->assertEquals(1, $result->extension);
    }

    public function testEstudianteInscritoReturnsTrueWhenExists(): void
    {
        $currentYear = date('Y');

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn([
                'carnet' => 20240001
            ]);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([ //usemos correctamente el filtro
                'carnet' => 20240001,
                'carrera' => 10,
                'extension' => 1,
                'anio' => $currentYear
            ])
            ->willReturn($resultSet);

        $result = $this->table->estudianteInscrito(
            20240001,
            10,
            1
        );

        $this->assertTrue($result); //retornemos true
    }

    public function testEstudianteInscritoReturnsFalseWhenNotExists(): void
    {
        $currentYear = date('Y');

        $resultSet = $this->createMock(ResultSet::class);

        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([
                'carnet' => 20240001,
                'carrera' => 10,
                'extension' => 1,
                'anio' => $currentYear
            ])
            ->willReturn($resultSet);

        $result = $this->table->estudianteInscrito(
            20240001,
            10,
            1
        );

        $this->assertFalse($result);
    }

    //fetchAll

    public function testFetchAllReturnsResultSet(): void
    {
        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $result = $this->table->fetchAll();

        $this->assertSame($resultSet, $result);
    }

    //obtenerCarreraActual

    public function testObtenerCarreraActualUsesSelectWith(): void
    {
        $carnet = 20240001;

        $resultSet = $this->createMock(ResultSet::class);

        $this->tableGateway->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($carnet) {
                return $select instanceof Select
                    && $select->getRawState()['table'] === 'inscripcion';
            }))
            ->willReturn($resultSet);

        $result = $this->table->obtenerCarreraActual($carnet);

        $this->assertSame($resultSet, $result);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/InscripcionTableTest.php

*/