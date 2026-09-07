<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\RolCarreraTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class RolCarreraTableTest extends TestCase
{
    private RolCarreraTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new RolCarreraTable($this->tableGateway);
    }

    //fetchAll

    public function testFetchAllReturnsResultSet(): void
    {
        // Creamos un mock del resultado esperado
        $resultSet = $this->createMock(ResultSet::class);

        // Verificamos que select() se llame exactamente una vez
        // y retorne el ResultSet mockeado
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with()
            ->willReturn($resultSet);

        // Ejecutamos el método a probar
        $result = $this->table->fetchAll();

        // Verificamos que el resultado retornado sea el mismo
        // que devolvió el TableGateway
        $this->assertSame($resultSet, $result);
    }

    //getCarrerasByRol

    public function testGetCarrerasByRolUsesCorrectRolId(): void
    {
        // ID de rol que utilizaremos para la prueba
        $rolId = 5;

        // Mock del ResultSet que retornará el select()
        $resultSet = $this->createMock(ResultSet::class);

        // Verificamos que select() reciba exactamente
        // el filtro esperado con el rol_id correcto
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'rol_id' => $rolId
            ])
            ->willReturn($resultSet);

        // Ejecutamos el método
        $this->table->getCarrerasByRol($rolId);
    }

    //test para verificar que devuelve y como lo devuelve getCarrerasByRol
    public function testGetCarrerasByRolReturnsCarreraIdsAsIntegers(): void
    {
        // Creamos registros simulados que normalmente
        // vendrían desde la base de datos
        $row1 = (object) ['carrera_id' => '1'];
        $row2 = (object) ['carrera_id' => '2'];
        $row3 = (object) ['carrera_id' => '3'];

        // Creamos un ArrayIterator para simular
        // el recorrido del ResultSet en el foreach
        $resultSet = new \ArrayIterator([
            $row1,
            $row2,
            $row3
        ]);

        // Simulamos el select() del TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'rol_id' => 1
            ])
            ->willReturn($resultSet);

        // Ejecutamos el método
        $result = $this->table->getCarrerasByRol(1);

        // Verificamos que los valores retornados
        // sean enteros y estén en el orden esperado
        $this->assertSame([1, 2, 3], $result);
    }

    //cuando el rol no tiene carrera asignada

    public function testGetCarrerasByRolReturnsEmptyArrayWhenNoResults(): void
    {
        // Simulamos un ResultSet vacío
        $resultSet = new \ArrayIterator([]);

        // Verificamos que select() se llame correctamente
        // y retorne un resultado vacío
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'rol_id' => 1
            ])
            ->willReturn($resultSet);

        // Ejecutamos el método
        $result = $this->table->getCarrerasByRol(1);

        // Validamos que el resultado sea un arreglo vacío
        $this->assertSame([], $result);
    }

    //insert

    public function testInsertCallsTableGatewayInsert(): void
    {
        // datos que simularemos insertar en la tabla
        $data = [
            'rol_id' => 1,
            'carrera_id' => 10
        ];
        // indicamos que esperamos una llamada al metodo insert()
        $this->tableGateway
            ->expects($this->once())
            // el metodo que esperamos que se ejecute es insert()
            ->method('insert')
            // verificamos que reciba array esperado
            ->with($data);
        // simulamos el valor que retornaría la base de datos
        // como ultimo ID insertado
        $this->tableGateway
            ->expects($this->once())
            // metodo que obtiene el ultimo ID insertado
            ->method('getLastInsertValue')
            // valor que retorna el mock
            ->willReturn(15);
        // ejecutamos el metodo que estamos probando
        $result = $this->table->insert($data);
        // verificamos que el metodo retorne el ID esperado
        $this->assertSame(15, $result);
    }

    public function testInsertReturnsLastInsertId(): void
    {
        // datos simulados para insertar
        $data = [
            'rol_id' => 1,
            'carrera_id' => 10
        ];
        // simulamos la llamada al insert
        $this->tableGateway
            ->expects($this->once())
            // metodo esperado
            ->method('insert')
            // datos esperados
            ->with($data);
        // simulamos el ultimo id insertado
        $this->tableGateway
            ->expects($this->once())
            // metodo esperado
            ->method('getLastInsertValue')
            // valor retornado por el mock
            ->willReturn('25');
        // ejecutamos el metodo
        $result = $this->table->insert($data);
        // verificamos que el resultado sea int
        $this->assertIsInt($result);
        // verificamos el valor esperado
        $this->assertSame(25, $result);
    }

    //deleteByRol

    public function testDeleteByRolUsesCorrectRolId(): void
    {
        // id del rol que eliminaremos
        $rolId = 5;
        // verificamos que delete() se llame una vez
        $this->tableGateway
            ->expects($this->once())
            // metodo esperado
            ->method('delete')
            // validamos que reciba el filtro correcto
            ->with([
                'rol_id' => $rolId
            ])
            // simulamos el total de filas eliminadas
            ->willReturn(1);
        // ejecutamos el metodo
        $result = $this->table->deleteByRol($rolId);
        // verificamos que retorne el valor esperado
        $this->assertSame(1, $result);
    }

    public function testDeleteByRolReturnsDeleteResult(): void
    {
        // id del rol a eliminar
        $rolId = 3;
        // simulamos el retorno del delete
        $deleteResult = 2;
        // configuramos el mock del delete
        $this->tableGateway
            ->expects($this->once())
            // metodo esperado
            ->method('delete')
            // filtro esperado
            ->with([
                'rol_id' => $rolId
            ])
            // valor retornado
            ->willReturn($deleteResult);
        // ejecutamos el metodo
        $result = $this->table->deleteByRol($rolId);
        // verificamos que retorne exactamente el valor esperado
        $this->assertSame($deleteResult, $result);
    }

    //getCarreraIdByRolId

    public function testGetCarreraIdByRolIdReturnsCarreraId()
    {
        // ID del rol que vamos a buscar
        $rolId = 1;

        // simulamos la fila que devolvería la base de datos
        $row = (object) [
            'rol_id' => 1,
            'carrera_id' => 15
        ];

        // creamos un mock del ResultSet de Laminas
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que current() devuelve la fila encontrada
        $resultSet
            ->method('current')
            ->willReturn($row);

        // esperamos que select() se ejecute una sola vez
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'rol_id' => $rolId
            ])
            ->willReturn($resultSet);

        // ejecutamos el método real que queremos probar
        $result = $this->table->getCarreraIdByRolId($rolId);

        // verificamos que el resultado sea un entero
        $this->assertIsInt($result);

        // verificamos que el valor sea el esperado
        $this->assertSame(15, $result);
    }

    //retorna null cuando no existe

    public function testGetCarreraIdByRolIdReturnsNullWhenNotFound()
    {
        // ID inexistente
        $rolId = 999;

        // creamos un mock del ResultSet
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que current() no encuentra ningún registro
        $resultSet
            ->method('current')
            ->willReturn(null);

        // simulamos la consulta a la base de datos
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with([
                'rol_id' => $rolId
            ])
            ->willReturn($resultSet);

        // ejecutamos el método real
        $result = $this->table->getCarreraIdByRolId($rolId);

        // verificamos que retorne null
        $this->assertNull($result);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/RolCarreraTableTest.php

*/