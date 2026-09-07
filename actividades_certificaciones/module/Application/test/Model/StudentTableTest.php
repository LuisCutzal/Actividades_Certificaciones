<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\StudentTable;
use Application\Model\Student;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class StudentTableTest extends TestCase
{
    private StudentTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new StudentTable($this->tableGateway);
    }

    //getByCarnet
    public function testGetByCarnetReturnsStudent(): void
    {
        // carnet que se utilizará para la búsqueda
        $carnet = 201700841;

        // simulamos el registro que vendría de la base de datos
        $row = [
            'carnet' => $carnet,
            'nombre' => 'Juan Perez'
        ];

        // mock del ResultSet que devuelve select()
        $resultSet = $this->createMock(ResultSet::class);

        // esperamos que current() sea llamado una vez
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($row);

        // esperamos que select() sea llamado con el carnet correcto
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['carnet' => $carnet])
            ->willReturn($resultSet);

        // ejecutamos el método que estamos probando
        $student = $this->table->getByCarnet($carnet);

        // verificamos que se retorne un objeto Student
        $this->assertInstanceOf(
            Student::class,
            $student
        );

        // verificamos que los datos fueron cargados correctamente
        $this->assertEquals($carnet, $student->carnet);
    }

    public function testGetByCarnetReturnsNullWhenStudentDoesNotExist(): void
    {
        // carnet que se utilizará para realizar la búsqueda
        $carnet = 201700841;

        // mock del ResultSet retornado por select()
        $resultSet = $this->createMock(ResultSet::class);

        // simulamos que no se encontró ningún registro
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        // verificamos que la búsqueda se haga usando el carnet correcto
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with(['carnet' => $carnet])
            ->willReturn($resultSet);

        // ejecutamos el método bajo prueba
        $student = $this->table->getByCarnet($carnet);

        // verificamos que el método retorne null
        $this->assertNull($student);
    }

    //insert

    public function testInsertCallsInsertWithCorrectData(): void
    {
        // datos que serán enviados para crear el estudiante
        $data = [
            'carnet' => 201700841,
            'nombre' => 'Juan Perez',
            'extension' => 1
        ];

        // esperamos que insert() sea llamado exactamente una vez
        // y que reciba el arreglo de datos correcto
        $this->tableGateway->expects($this->once())
            ->method('insert')
            ->with($data);

        // el método insert() llama también a getLastInsertValue()
        // por lo que debemos simular su ejecución
        $this->tableGateway->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn(1);

        // ejecutamos el método bajo prueba
        $this->table->insert($data);
    }

    public function testInsertReturnsLastInsertValue(): void
    {
        // datos simulados para la inserción
        $data = [
            'carnet' => 201700841,
            'nombre' => 'Juan Perez',
            'extension' => 1
        ];

        // simulamos la ejecución del insert
        $this->tableGateway->expects($this->once())
            ->method('insert')
            ->with($data);

        // simulamos el último id generado por la base de datos
        $this->tableGateway->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn(25);

        // ejecutamos el método bajo prueba
        $result = $this->table->insert($data);

        // verificamos que se retorne el valor obtenido
        // desde getLastInsertValue()
        $this->assertEquals(25, $result);
    }

    //fetchAll

    public function testFetchAllReturnsSelectResult(): void
    {
        // mock del resultado que devolverá el TableGateway
        $resultSet = $this->createMock(ResultSet::class);

        // verificamos que select() sea llamado exactamente una vez
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with() // fetchAll() no envía parámetros
            ->willReturn($resultSet);

        // ejecutamos el método bajo prueba
        $result = $this->table->fetchAll();

        // verificamos que se retorne exactamente el mismo ResultSet
        $this->assertSame($resultSet, $result);
    }

    //fetchByCarnets

    public function testFetchByCarnetsReturnsVacio(): void
    {
        // arreglo vacío de carnets
        $carnets = [];

        // extensión simulada
        $extension = 1;

        // carreras del usuario simuladas
        $carrerasUsuario = [1, 2];

        // verificamos que no se intente obtener el objeto Sql
        // ya que la función debe terminar antes de llegar a esa parte
        $this->tableGateway->expects($this->never())
            ->method('getSql');

        // ejecutamos el método bajo prueba
        $result = $this->table->fetchByCarnets(
            $carnets,
            $extension,
            $carrerasUsuario
        );

        // verificamos que retorne un arreglo vacío
        $this->assertSame([], $result);
    }

    //buscarPorNombreOCarnetYCarnets

    public function testBuscarPorNombreOCarnetYCarnetsReturns(): void
    {
        // texto de búsqueda simulado
        $q = 'Juan';

        // lista vacía de carnets
        $carnets = [];

        // carreras simuladas
        $carreras = [1, 2];

        // extensión simulada
        $extension = 1;

        // verificamos que NO se intente construir una consulta
        // ya que la función debe terminar inmediatamente
        $this->tableGateway->expects($this->never())
            ->method('getSql');

        // ejecutamos el método bajo prueba
        $result = $this->table->buscarPorNombreOCarnetYCarnets(
            $q,
            $carnets,
            $carreras,
            $extension
        );

        // verificamos que se retorne un arreglo vacío
        $this->assertSame([], $result);
    }

    //getPorCarnets

    public function testGetPorCarnetsSinCarnetsRetornaVacio(): void
    {
        // lista vacía de carnets
        $carnets = [];

        // verificamos que nunca se consulte el TableGateway
        // porque la función debe terminar inmediatamente
        $this->tableGateway->expects($this->never())
            ->method('select');

        // ejecutamos el método bajo prueba
        $result = $this->table->getPorCarnets(
            $carnets,
        );

        // verificamos que retorne un arreglo vacío
        $this->assertSame([], $result);
    }

    public function testGetPorCarnetsRetornaEstudiantes(): void
    {
        // lista de carnets que se buscarán
        $carnets = [201700841, 201700842];

        // registros simulados que devolvería la consulta
        $students = [
            [
                'carnet' => 201700841,
                'nombre' => 'Juan Perez'
            ],
            [
                'carnet' => 201700842,
                'nombre' => 'Maria Lopez'
            ]
        ];

        // usamos un ArrayIterator porque iterator_to_array()
        // necesita un objeto iterable real
        $resultSet = new \ArrayIterator($students);

        // verificamos que se llame al método select()
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with($this->isInstanceOf(\Closure::class)) //la lógica interna del Closure.
            ->willReturn($resultSet);

        // ejecutamos el método bajo prueba
        $result = $this->table->getPorCarnets(
            $carnets,
        );

        // verificamos que se retornen los registros esperados
        $this->assertSame($students, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/StudentTableTest.php

*/