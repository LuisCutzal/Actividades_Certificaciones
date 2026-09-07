<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\RolesPermisosTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class RolesPermisosTableTest extends TestCase
{
    private RolesPermisosTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new RolesPermisosTable($this->tableGateway);
    }

    //fetchAll que retorne lo que devuelve
    public function testFetchAllReturnsResultSet(): void
    {
        // crear mock del resultset que retornara select()
        $resultSet = $this->createMock(ResultSet::class);

        // esperar que select() sea llamado una vez sin parametros
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with()
            ->willReturn($resultSet);

        // ejecutar el metodo fetchAll()
        $result = $this->table->fetchAll();

        // verificar que el resultado sea el mismo resultset retornado
        $this->assertSame($resultSet, $result);
    }

    //asignarPermiso
    public function testAsignarPermisoCallsInsertWithCorrectData(): void
    {
        // datos que se enviara al metodo insert()
        $data = [
            'rol_id' => 1,
            'permiso_id' => 5,
        ];

        // indicar que insert() debe ejecutarse exactamente una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es insert()
            ->method('insert')

            // verificar que insert() reciba exactamente el arreglo definido
            ->with($data);

        // ejecutar el metodo asignarPermiso() enviando los datos
        $this->table->asignarPermiso($data);
    }

    //deleteByRol

    public function testDeleteByRolCallsDeleteWithCorrectRolId(): void
    {
        // definir id del rol que sera eliminado
        $rolId = 10;

        // definir condicion esperada para el metodo delete()
        $where = [
            'rol_id' => $rolId,
        ];

        // indicar que delete() debe ejecutarse exactamente una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es delete()
            ->method('delete')

            // verificar que delete() reciba el arreglo correcto
            ->with($where);

        // ejecutar el metodo deleteByRol() enviando el id del rol
        $this->table->deleteByRol($rolId);
    }

    public function testDeleteByRolReturnsDeleteResult(): void
    {
        // definir id del rol que sera enviado al metodo
        $rolId = 5;

        // definir valor que retornara delete()
        $deleteResult = 1;

        // indicar que delete() debe ejecutarse exactamente una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es delete()
            ->method('delete')

            // verificar que delete() reciba el arreglo correcto
            ->with([
                'rol_id' => $rolId,
            ])

            // indicar el valor que retornara delete()
            ->willReturn($deleteResult);

        // ejecutar el metodo deleteByRol()
        $result = $this->table->deleteByRol($rolId);

        // verificar que el resultado retornado sea el esperado
        $this->assertSame($deleteResult, $result);
    }

    //getPermisosByRolId

    public function testGetPermisosByRolIdReturnsArrayOfPermisoIds(): void
    {
        // crear arreglo que simula los registros retornados por select()
        $resultSet = [
            (object) ['permiso_id' => 1],
            (object) ['permiso_id' => 2],
            (object) ['permiso_id' => 3],
        ];

        // definir id del rol que sera consultado
        $rolId = 10;

        // indicar que select() debe ejecutarse una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es select()
            ->method('select')

            // verificar que select() reciba el where correcto
            ->with([
                'rol_id' => $rolId,
            ])

            // retornar el arreglo simulado
            ->willReturn($resultSet);

        // ejecutar el metodo getPermisosByRolId()
        $result = $this->table->getPermisosByRolId($rolId);

        // verificar que solo se retornen los permiso_id
        $this->assertSame([1, 2, 3], $result);
    }

    public function testGetPermisosByRolIdUsesCorrectWhereCondition(): void
    {
        // definir id del rol que sera enviado al metodo
        $rolId = 5;

        // indicar que select() debe ejecutarse una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es select()
            ->method('select')

            // verificar que select() reciba el where correcto
            ->with([
                'rol_id' => $rolId,
            ])

            // retornar arreglo vacio para completar la ejecucion del metodo
            ->willReturn([]);

        // ejecutar el metodo getPermisosByRolId()
        $this->table->getPermisosByRolId($rolId);
    }

    public function testGetPermisosByRolIdReturnsEmptyArrayWhenNoResults(): void
    {
        // definir id del rol que sera consultado
        $rolId = 1;

        // indicar que select() debe ejecutarse una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es select()
            ->method('select')

            // retornar arreglo vacio para simular que no existen registros
            ->willReturn([]);

        // ejecutar el metodo getPermisosByRolId()
        $result = $this->table->getPermisosByRolId($rolId);

        // verificar que el metodo retorne un arreglo vacio
        $this->assertSame([], $result);
    }

    public function testInsertCallsInsertWithCorrectData(): void
    {
        // crear arreglo de datos que sera enviado al metodo insert()
        $data = [
            'rol_id' => 1,
            'permiso_id' => 3,
        ];

        // indicar que insert() debe ejecutarse exactamente una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es insert()
            ->method('insert')

            // verificar que insert() reciba el arreglo correcto
            ->with($data);

        // ejecutar el metodo insert()
        $this->table->insert($data);
    }
    
    public function testInsertReturnsInsertResult(): void
    {
        // crear arreglo de datos que sera enviado al metodo insert()
        $data = [
            'rol_id' => 2,
            'permiso_id' => 7,
        ];

        // definir valor que retornara insert()
        $insertResult = 1;

        // indicar que insert() debe ejecutarse una vez
        $this->tableGateway
            ->expects($this->once())

            // indicar que el metodo esperado es insert()
            ->method('insert')

            // verificar que insert() reciba el arreglo correcto
            ->with($data)

            // definir el valor que retornara insert()
            ->willReturn($insertResult);

        // ejecutar el metodo insert()
        $result = $this->table->insert($data);

        // verificar que el resultado retornado sea el esperado
        $this->assertSame($insertResult, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/RolesPermisosTableTest.php

*/