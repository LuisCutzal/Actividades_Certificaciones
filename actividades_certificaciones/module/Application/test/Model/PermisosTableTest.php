<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\PermisosTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;

class PermisosTableTest extends TestCase
{
    private PermisosTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new PermisosTable($this->tableGateway);
    }

    //getPermisosByRol

    public function testGetPermisosByRolReturnsEmptyArrayWhenNoResults(): void
    {
        $rolId = 1;

        // Mock Select
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->method('quantifier')
            ->willReturnSelf();

        $select->method('columns')
            ->willReturnSelf();

        $select->method('join')
            ->willReturnSelf();

        $select->method('where')
            ->willReturnSelf();

        // Mock Sql
        $sql = $this->createMock(Sql::class);

        $sql->method('select')
            ->willReturn($select);

        // Mock TableGateway
        $this->tableGateway->method('getSql')
            ->willReturn($sql);

        $this->tableGateway->method('selectWith')
            ->willReturn(new \ArrayIterator([]));

        $result = $this->table->getPermisosByRol($rolId);

        $this->assertSame([], $result);
    }

    public function testGetPermisosByRolReturnsSinglePermission(): void
    {
        $rolId = 1;

        // Simulamos fila de BD
        $dbResult = new \ArrayIterator([
            ['nombre' => 'crear_usuario']
        ]);

        // Mock Select (cadena SQL)
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->method('quantifier')->willReturnSelf();
        $select->method('columns')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        // Mock Sql
        $sql = $this->createMock(Sql::class);
        $sql->method('select')->willReturn($select);

        // Configurar TableGateway
        $this->tableGateway->method('getSql')->willReturn($sql);
        $this->tableGateway->method('selectWith')->willReturn($dbResult);

        // Ejecutar
        $result = $this->table->getPermisosByRol($rolId);

        // Assert
        $this->assertSame(['crear_usuario'], $result);
    }

    //Devuelve múltiples permisos
    public function testGetPermisosByRolReturnsMultiplePermissions(): void
    {
        $rolId = 1;

        // Simulación de múltiples filas de BD
        $dbResult = new \ArrayIterator([
            ['nombre' => 'crear_usuario'],
            ['nombre' => 'editar_usuario']
        ]);

        // Mock Select
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->method('quantifier')->willReturnSelf();
        $select->method('columns')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        // Mock Sql
        $sql = $this->createMock(Sql::class);
        $sql->method('select')->willReturn($select);

        // Mock TableGateway
        $this->tableGateway->method('getSql')->willReturn($sql);
        $this->tableGateway->method('selectWith')->willReturn($dbResult);

        // Ejecutar
        $result = $this->table->getPermisosByRol($rolId);

        // Assert
        $this->assertSame(
            ['crear_usuario', 'editar_usuario'],
            $result
        );
    }

    //Ignora columnas extra

    public function testGetPermisosByRolIgnoresExtraColumns(): void
    {
        $rolId = 1;

        // Simulación de fila con columnas extra
        $dbResult = new \ArrayIterator([
            [
                'nombre' => 'crear_actividad',
                'descripcion' => 'Es para crear una actiivdad'
            ]
        ]);

        // Mock Select
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->method('quantifier')->willReturnSelf();
        $select->method('columns')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        // Mock Sql
        $sql = $this->createMock(Sql::class);
        $sql->method('select')->willReturn($select);

        // Mock TableGateway
        $this->tableGateway->method('getSql')->willReturn($sql);
        $this->tableGateway->method('selectWith')->willReturn($dbResult);

        // Ejecutar
        $result = $this->table->getPermisosByRol($rolId);

        // Assert
        $this->assertSame(['crear_actividad'], $result);
    }

    public function testGetPermisosByRolPassesRolIdToWhere(): void
    {
        $rolId = 10;

        // Mock Select
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->method('quantifier')->willReturnSelf();
        $select->method('columns')->willReturnSelf();
        $select->method('join')->willReturnSelf();

        $select->expects($this->once())
            ->method('where')
            ->with(['rp.rol_id' => 10])
            ->willReturnSelf();

        // Mock Sql
        $sql = $this->createMock(Sql::class);
        $sql->method('select')->willReturn($select);

        // Mock TableGateway
        $this->tableGateway->method('getSql')->willReturn($sql);
        $this->tableGateway->method('selectWith')->willReturn(new \ArrayIterator([]));

        $result = $this->table->getPermisosByRol($rolId);

        $this->assertSame([], $result);
    }

    //fetchALL

    public function testFetchAllReturnsResultFromTableGatewaySelect(): void
    {
        // Mock del resultado tipo ResultSet
        $resultSet = $this->createMock(ResultSet::class);

        // Esperamos que se llame select()
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        // Ejecutar
        $result = $this->table->fetchAll();

        // Verificar que devuelve exactamente lo que retorna select()
        $this->assertSame($resultSet, $result);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/PermisosTableTest.php

*/