<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\RolTable;
use Application\Model\Tipo;
use Application\Model\TipoTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class RolTableTest extends TestCase
{
    private RolTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new RolTable($this->tableGateway);
    }

    private function makeRol(int $id, string $nombre, int $tipoId = 1): \ArrayObject
    {
        // crea un rol simulado reutilizable para todos los tests
        return new \ArrayObject([
            'id' => $id,
            'nombre' => $nombre,
            'tipo_id' => $tipoId
        ]);
    }

    private function makeInsertData(int $id, string $nombre = 'Admin', int $tipoId = 1): array
    {
        // crea data de inserción reutilizable
        return [
            'id' => $id,
            'nombre' => $nombre,
            'tipo_id' => $tipoId
        ];
    }

    //crearRol y devuelve el ultimo id
    public function testCrearRolReturnsLastInsertId()
    {
        // arreglo de datos que se insertarán en la tabla
        $data = $this->makeInsertData(1, 'Admin', 1);

        // simulamos el insert (no retorna nada)
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);

        // simulamos el último ID insertado
        $this->tableGateway
            ->method('getLastInsertValue')
            ->willReturn(10);

        // ejecutamos el método
        $result = $this->table->crearRol($data);

        // verificamos que retorna el ID correcto
        $this->assertSame(10, $result);
    }

    //getRolById -> devuelve rol correcto

    public function testGetRolByIdReturnsCorrectRole()
    {
        // simulamos un objeto de rol (resultado de la BD)
        $rol = $this->makeInsertData(1, 'Admin', 1);

        // simulamos el ResultSet de Laminas
        $resultSetMock = $this->createMock(ResultSet::class);

        // configuramos que current() devuelva el rol simulado
        $resultSetMock
            ->method('current')
            ->willReturn($rol);

        // esperamos que el TableGateway reciba el select con el ID correcto
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSetMock);

        // ejecutamos el método a probar
        $result = $this->table->getRolById(1);

        // verificamos que el resultado sea el rol esperado
        $this->assertSame($rol, $result);
    }

    //updateRol

    public function testUpdateRolCallsUpdateWithCorrectData()
    {
        // ID del rol que queremos actualizar
        $id = 5;

        // datos que se enviarán para actualizar el rol
        $data = $this->makeInsertData(5, 'Personal administrativo', 1);

        // simulamos lo que devuelve el método update
        $expectedResult = 1;

        // configuramos el mock del TableGateway para esperar la llamada a update
        $this->tableGateway
            ->expects($this->once()) // debe llamarse exactamente una vez
            ->method('update') // método que estamos simulando
            ->with(
                $data, // primer parámetro: datos a actualizar
                ['id' => $id] // segundo parámetro: condición WHERE
            )
            ->willReturn($expectedResult); // simulamos retorno de update

        // ejecutamos el método real del modelo
        $result = $this->table->updateRol($id, $data);

        // verificamos que el resultado sea el esperado
        $this->assertSame($expectedResult, $result);
    }

    //buscarRol

    public function testBuscarRolReturnsArrayOfRoles()
    {
        // texto de búsqueda que se enviará al método
        $query = 'Admin';

        // simulamos el primer rol que devolvería la BD
        $rol1 = $this->makeRol(1, 'Administrador', 2);

        // simulamos el segundo rol que devolvería la BD

        $rol2 = $this->makeRol(2, 'admin', 1);

        // creamos ResultSet real de Laminas
        $resultSet = new ResultSet();

        // inicializamos el ResultSet con datos simulados
        $resultSet->initialize([$rol1, $rol2]);

        // configuramos el mock del TableGateway
        $this->tableGateway
            ->expects($this->once()) // debe llamarse solo una vez
            ->method('select') // interceptamos select
            ->willReturn($resultSet); // devolvemos datos simulados

        // ejecutamos el método real
        $result = $this->table->buscarRol($query);

        // verificamos que el resultado sea un array
        $this->assertIsArray($result);

        // verificamos que tenga 2 elementos
        $this->assertCount(2, $result);

        // validamos primer elemento
        $this->assertSame($rol1, $result[0]);

        // validamos segundo elemento
        $this->assertSame($rol2, $result[1]);
    }

    //getRolConTipo

    public function testGetRolConTipoReturnsCombinedData()
    {
        // ID del rol a consultar
        $rolId = 1;

        // simulamos el rol usando helper reutilizable
        $rol = $this->makeInsertData(1, 'Personal administrativo', 1);

        // simulamos objeto Tipo REAL (NO stdClass)
        $tipo = $this->createMock(Tipo::class);

        // configuramos propiedad del mock (nombre del tipo)
        $tipo->nombre = 'Sistema';

        // mock del ResultSet de Laminas
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos retorno de current()
        $resultSet
            ->method('current')
            ->willReturn((object) $rol);

        // simulamos select del TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => $rolId])
            ->willReturn($resultSet);

        // mock de TipoTable
        $tipoTableMock = $this->createMock(TipoTable::class);

        // simulamos método getTipoById con tipo correcto
        $tipoTableMock
            ->expects($this->once())
            ->method('getTipoById')
            ->with(1)
            ->willReturn($tipo); // ahora compatible con ?Tipo

        // ejecutamos método real
        $result = $this->table->getRolConTipo($rolId, $tipoTableMock);

        // validamos estructura
        $this->assertIsArray($result);

        // validamos datos del rol
        $this->assertSame(1, $result['id']);
        $this->assertSame('Personal administrativo', $result['nombre']);
        $this->assertSame(1, $result['tipo_id']);

        // validamos datos del tipo combinado
        $this->assertSame('Sistema', $result['tipo_nombre']);
        //$this->assertSame('Sistema', $result['sidebar']);
    }

    //getTipoIdByRolId

    public function testGetTipoIdByRolIdReturnsTypeId()
    {
        // ID del rol a consultar
        $rolId = 1;

        // simulamos el rol que viene de la base de datos
        $rol = (object) [
            'id' => 1,
            'tipo_id' => 2
        ];

        // mock del ResultSet de Laminas
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que current() devuelve el rol
        $resultSet
            ->method('current')
            ->willReturn($rol);

        // esperamos que el select se llame con el ID correcto
        $this->tableGateway
            ->expects($this->once()) // solo una llamada
            ->method('select') // método interceptado
            ->with(['id' => $rolId]) // condición WHERE
            ->willReturn($resultSet); // resultado simulado

        // ejecutamos el método real
        $result = $this->table->getTipoIdByRolId($rolId);

        // validamos que el resultado sea entero
        $this->assertIsInt($result);

        // validamos el valor exacto
        $this->assertSame(2, $result);
    }

    public function testGetTipoIdByRolIdReturnsNullWhenNotFound()
    {
        // ID inexistente
        $rolId = 99;

        // mock del ResultSet vacío
        $resultSet = $this->createMock(ResultSet::class);

        // current() devuelve null
        $resultSet
            ->method('current')
            ->willReturn(null);

        // simulamos select
        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => $rolId])
            ->willReturn($resultSet);

        // ejecutamos método
        $result = $this->table->getTipoIdByRolId($rolId);

        // validamos null
        $this->assertNull($result);
    }

    //getExtensionByRolId

    public function testGetExtensionByRolIdReturnsExtension()
    {
        // ID del rol que vamos a consultar
        $rolId = 1;

        // simulamos el registro que devolvería la base de datos
        $row = (object) [
            'id' => 1,
            'extension' => 1234
        ];

        // creamos un mock del ResultSet de Laminas
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que current() devuelve el registro encontrado
        $resultSet
            ->method('current')
            ->willReturn($row);

        // esperamos que se ejecute un select usando el ID correcto
        $this->tableGateway
            ->expects($this->once()) // debe llamarse una sola vez
            ->method('select') // interceptamos el select
            ->with([
                'id' => $rolId
            ]) // verificamos la condición WHERE
            ->willReturn($resultSet); // devolvemos el ResultSet simulado

        // ejecutamos el método real
        $result = $this->table->getExtensionByRolId($rolId);

        // verificamos que el resultado sea un entero
        $this->assertIsInt($result);

        // verificamos que el valor retornado sea el esperado
        $this->assertSame(1234, $result);
    }

    //retorna null cuando no existe el rol

    public function testGetExtensionByRolIdReturnsNullWhenNotFound()
    {
        // ID inexistente para la prueba
        $rolId = 999;

        // creamos un mock del ResultSet
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que current() no encuentra registros
        $resultSet
            ->method('current')
            ->willReturn(null);

        // simulamos la consulta a la base de datos
        $this->tableGateway
            ->expects($this->once()) // debe ejecutarse una sola vez
            ->method('select') // interceptamos select
            ->with([
                'id' => $rolId
            ]) // verificamos el WHERE
            ->willReturn($resultSet); // devolvemos resultado vacío

        // ejecutamos el método real
        $result = $this->table->getExtensionByRolId($rolId);

        // verificamos que el resultado sea null
        $this->assertNull($result);
    }

    //fetchAll

    public function testFetchAllReturnsResultSet()
    {
        // creamos un mock del ResultSet que simulará la respuesta de la BD
        $resultSet = $this->createMock(ResultSet::class);

        // esperamos que select() se ejecute exactamente una vez
        $this->tableGateway
            ->expects($this->once()) // debe llamarse una sola vez
            ->method('select') // interceptamos el método select
            ->with() // fetchAll() no envía parámetros
            ->willReturn($resultSet); // devolvemos el ResultSet simulado

        // ejecutamos el método real
        $result = $this->table->fetchAll();

        // verificamos que se retorne exactamente el mismo ResultSet
        $this->assertSame($resultSet, $result);
    }

    //getRol

    public function testGetRolReturnsResultSet()
    {
        // creamos un mock del ResultSet
        $resultSet = $this->createMock(ResultSet::class);

        // esperamos que select() se ejecute una sola vez
        $this->tableGateway
            ->expects($this->once()) // una llamada
            ->method('select') // interceptamos select
            ->willReturn($resultSet); // devolvemos el ResultSet simulado

        // ejecutamos el método real
        $result = $this->table->getRol();

        // verificamos que retorne exactamente el mismo ResultSet
        $this->assertSame($resultSet, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/RolTableTest.php

*/