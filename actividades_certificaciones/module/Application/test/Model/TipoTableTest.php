<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\TipoTable;
use Application\Model\Tipo;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;

class TipoTableTest extends TestCase
{
    private TipoTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new TipoTable($this->tableGateway);
    }

    //getTipoById

    public function testGetTipoByIdReturnsTipoWhenRecordExists(): void
    {
        // Simula un registro que vendría de la base de datos.
        $row = new \ArrayObject([
            'id' => 1,
            'nombre' => 'admin'
        ]);

        // Mock del ResultSet que devuelve TableGateway::select().
        $resultSet = $this->createMock(ResultSet::class);

        // Simula que current() retorna el registro encontrado.
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($row);

        // Verifica que select() sea llamado con el criterio correcto.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        // Ejecuta el método que estamos probando.
        $result = $this->table->getTipoById(1);

        // Verifica que el resultado sea un objeto Tipo.
        $this->assertInstanceOf(Tipo::class, $result);

        // Verifica que los datos hayan sido cargados correctamente.
        $this->assertEquals(1, $result->id);
        $this->assertEquals('admin', $result->nombre);
    }

    public function testTipoPorIdIngenieria(): void
    {
        // Simula el ResultSet devuelto por select().
        $resultSet = $this->createMock(ResultSet::class);

        // Simula que no se encontró ningún registro.
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        // Verifica que se consulte usando el id recibido.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['id' => 999])
            ->willReturn($resultSet);

        // Ejecuta el método.
        $result = $this->table->getTipoById(999);

        // Debe retornar null porque no existe el registro.
        $this->assertNull($result);
    }

    //fetchAll

    public function testFetchAllVacio(): void
    {
        // Simula que la consulta no devuelve registros.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->willReturn([]);

        // Ejecuta el método.
        $result = $this->table->fetchAll();

        // Verifica que retorne un arreglo.
        $this->assertIsArray($result);

        // Verifica que el arreglo esté vacío.
        $this->assertEmpty($result);
    }

    public function testFetchAllRetornaTipos(): void
    {
        // Simula el primer registro obtenido desde la base de datos.
        $row1 = new \ArrayObject([
            'id' => 1,
            'nombre' => 'admin'
        ]);

        // Simula el segundo registro obtenido desde la base de datos.
        $row2 = new \ArrayObject([
            'id' => 2,
            'nombre' => 'personal admin'
        ]);

        // Configura el mock para que select() devuelva dos registros.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->willReturn([$row1, $row2]);

        // Ejecuta el método que se está evaluando.
        $result = $this->table->fetchAll();

        // Verifica que el resultado sea un arreglo.
        $this->assertIsArray($result);

        // Verifica que se hayan retornado exactamente dos elementos.
        $this->assertCount(2, $result);

        // Verifica que el primer elemento sea un objeto Tipo.
        $this->assertInstanceOf(Tipo::class,  $result[0]);

        // Verifica que el segundo elemento sea un objeto Tipo.
        $this->assertInstanceOf(Tipo::class, $result[1]);

        // Verifica que el primer objeto contenga el id esperado.
        $this->assertEquals(1, $result[0]->id);

        // Verifica que el primer objeto contenga el nombre esperado.
        $this->assertEquals('admin', $result[0]->nombre);

        // Verifica que el segundo objeto contenga el id esperado.
        $this->assertEquals(2, $result[1]->id);

        // Verifica que el segundo objeto contenga el nombre esperado.
        $this->assertEquals('personal admin', $result[1]->nombre);
    }

    //getTipoByNombre

    public function testObtenerTipoPorNombre(): void
    {
        // Simula un registro encontrado en la base de datos.
        $row = new \ArrayObject([
            'id' => 1,
            'nombre' => 'admin'
        ]);

        // Crea un mock del ResultSet devuelto por select().
        $resultSet = $this->createMock(ResultSet::class);

        // Simula que current() retorna el registro encontrado.
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($row);

        // Verifica que la búsqueda se realice utilizando el nombre recibido.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['nombre' => 'admin'])
            ->willReturn($resultSet);

        // Ejecuta el método que se está evaluando.
        $result = $this->table->getTipoByNombre('admin');

        // Verifica que el resultado sea una instancia de Tipo.
        $this->assertInstanceOf(Tipo::class, $result);

        // Verifica que el id haya sido asignado correctamente.
        $this->assertEquals(1, $result->id);

        // Verifica que el nombre haya sido asignado correctamente.
        $this->assertEquals(
            'admin',
            $result->nombre
        );
    }

    public function testTipoPorNombreInexistente(): void
    {
        // Crea un mock del ResultSet devuelto por select().
        $resultSet = $this->createMock(ResultSet::class);

        // Simula que la consulta no encontró ningún registro.
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        // Verifica que la búsqueda se realice utilizando el nombre recibido.
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['nombre' => 'Ingenieria']) //-> pusimos de ejemplo ingenieria pero puede ser cualquier dato que no exista
            ->willReturn($resultSet);

        // Ejecuta el método que se está evaluando.
        $result = $this->table->getTipoByNombre('Ingenieria');

        // Verifica que el resultado sea null.
        $this->assertNull($result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/TipoTableTest.php

*/