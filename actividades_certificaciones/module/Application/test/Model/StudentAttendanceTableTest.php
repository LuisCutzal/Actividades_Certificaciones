<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\StudentAttendanceTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class StudentAttendanceTableTest extends TestCase
{
    private StudentAttendanceTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new StudentAttendanceTable($this->tableGateway);
    }

    //insert
    public function testInsertCallsTableGatewayInsertWithCorrectData(): void
    {
        // datos que se enviarán para registrar una asistencia
        $data = [
            'carnet' => 201700841,
            'id_activity' => 5,
            'fecha' => '2026-05-31'
        ];

        // se espera que el método insert() del TableGateway
        // sea ejecutado exactamente una vez
        $this->tableGateway->expects($this->once())

            // se verifica específicamente la llamada al método insert()
            ->method('insert')

            // se valida que los datos enviados sean exactamente
            // los mismos que recibe StudentAttendanceTable::insert()
            ->with($data);

        // ejecuta el método bajo prueba
        $this->table->insert($data);
    }

    //exist

    public function testExistsReturnsTrueWhenRecordExists(): void
    {
        // identificadores que se utilizarán para buscar la asistencia
        $studentId = 201700841;
        $activityId = 5;

        // se crea un mock del ResultSet que devolverá el TableGateway
        $resultSet = $this->createMock(ResultSet::class);

        // se simula que la consulta encontró un registro
        // current() devuelve cualquier objeto distinto de null
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(new \ArrayObject());

        // se espera que select() sea llamado una sola vez
        // con los criterios correctos de búsqueda
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([
                'carnet' => $studentId,
                'id_activity' => $activityId,
            ])
            ->willReturn($resultSet);

        // ejecuta el método bajo prueba
        $result = $this->table->exists($studentId, $activityId);

        // verifica que el método indique que el registro existe
        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseWhenRecordDoesNotExist(): void
    {
        // identificadores utilizados para realizar la búsqueda
        $studentId = 201700841;
        $activityId = 5;

        // se crea un mock del ResultSet que será retornado por select()
        $resultSet = $this->createMock(ResultSet::class);

        // se simula que la consulta no encontró ningún registro
        // current() devuelve null cuando el ResultSet está vacío
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        // se espera que select() sea ejecutado una sola vez
        // utilizando los criterios correctos de búsqueda
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([
                'carnet' => $studentId,
                'id_activity' => $activityId,
            ])
            ->willReturn($resultSet);

        // ejecuta el método que se desea probar
        $result = $this->table->exists($studentId, $activityId);

        // verifica que el método indique que el registro no existe
        $this->assertFalse($result);
    }

    //delete

    public function testDeleteCallsTableGatewayDeleteWithCorrectWhere(): void
    {
        // condiciones que se utilizarán para eliminar el registro
        $where = [
            'carnet' => 201700841,
            'id_activity' => 5,
        ];

        // se espera que el método delete() del TableGateway
        // sea ejecutado exactamente una vez
        $this->tableGateway->expects($this->once())

            // verifica que se invoque específicamente delete()
            ->method('delete')

            // valida que las condiciones enviadas sean exactamente
            // las mismas que recibe StudentAttendanceTable::delete()
            ->with($where);

        // ejecuta el método bajo prueba
        $this->table->delete($where);
    }

    public function testDeleteReturnsDeleteResult(): void
    {
        // condiciones utilizadas para eliminar el registro
        $where = [
            'carnet' => 201700841,
            'id_activity' => 5,
        ];

        // se simula que el TableGateway eliminó un registro
        // y retorna la cantidad de filas afectadas
        $this->tableGateway->expects($this->once())
            ->method('delete')
            ->with($where)
            ->willReturn(1);

        // ejecuta el método bajo prueba
        $result = $this->table->delete($where);

        // verifica que StudentAttendanceTable retorne exactamente
        // el mismo resultado devuelto por el TableGateway
        $this->assertEquals(1, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Model/StudentAttendanceTableTest.php

*/