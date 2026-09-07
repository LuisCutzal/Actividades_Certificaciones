<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Model\StudentTable;
use Application\Model\AttendanceListStudentTable;
use Application\Model\CarreraEstudianteTable;
use Application\Service\EstudianteService;
use Application\Model\Student;


class EstudianteServiceTest extends TestCase
{
    private EstudianteService $estudianteService;
    private StudentTable $studentTable;
    private AttendanceListStudentTable $attendanceListStudentTable;
    private CarreraEstudianteTable $carreraEstudianteTable;
    private Student $student;

    protected function setUp(): void
    {
        $this->studentTable = $this->createMock(StudentTable::class);
        $this->attendanceListStudentTable = $this->createMock(AttendanceListStudentTable::class);
        $this->carreraEstudianteTable = $this->createMock(CarreraEstudianteTable::class);
        $this->student = $this->createMock(Student::class);

        $this->estudianteService = new EstudianteService(
            $this->studentTable,
            $this->attendanceListStudentTable,
            $this->carreraEstudianteTable
        );
    }

    //listarTodos

    public function testListar(): void
    {
        // define la respuesta esperada del modelo
        $estudiantes = [
            ['carnet' => 201700841],
            ['carnet' => 201700842]
        ];

        // verifica que fetchall sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('fetchAll')
            // define el valor que devolvera el mock
            ->willReturn($estudiantes);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->listarTodos();

        // verifica que el resultado sea el esperado
        $this->assertSame($estudiantes, $resultado);
    }

    //obtenerPorCarnet

    public function testObtener(): void
    {
        // define el carnet a buscar
        $carnet = 201700841;

        // verifica que getbycarnet sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('getByCarnet')
            // verifica que reciba el carnet correcto
            ->with($carnet)
            // define el valor que devolvera el mock
            ->willReturn($this->student);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->obtenerPorCarnet($carnet);

        // verifica que el resultado sea el esperado
        $this->assertSame($this->student, $resultado);
    }

    //obtenerEstudiante

    public function testEstudiante(): void
    {
        // define el carnet a buscar
        $carnet = 201700841;

        // verifica que getbycarnet sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('getByCarnet')
            // verifica que reciba el carnet correcto
            ->with($carnet)
            // define el valor que devolvera el mock
            ->willReturn($this->student);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->obtenerEstudiante($carnet);

        // verifica que el resultado sea el esperado
        $this->assertSame($this->student, $resultado);
    }

    //obtenerActividadesDelEstudiante

    public function testActividades(): void
    {
        // define el id del estudiante
        $idStudent = 10;


        $buscar = 'actividad';

        // define las actividades esperadas
        $actividades = [
            ['id' => 1, 'nombre' => 'actividad 1'],
            ['id' => 2, 'nombre' => 'actividad 2']
        ];

        // verifica que getactividadesbystudent sea llamado una vez
        $this->attendanceListStudentTable->expects($this->once())
            ->method('getActividadesByStudent')
            // verifica que reciba los parametros correctos
            ->with($idStudent, $buscar)
            // define el valor que devolvera el mock
            ->willReturn($actividades);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService
            ->obtenerActividadesDelEstudiante($idStudent, $buscar);

        // verifica que el resultado sea el esperado
        $this->assertSame($actividades, $resultado);
    }

    //listarPorCarreras
    public function testCarrerasVacio(): void
    {

        $carreras = [];

        // define la extension
        $extension = 1;

        // verifica que no se consulte la tabla de carreras
        $this->carreraEstudianteTable->expects($this->never())
            ->method('getCarnetsPorCarrera');

        // verifica que no se consulte la tabla de estudiantes
        $this->studentTable->expects($this->never())
            ->method('fetchByCarnets');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService
            ->listarPorCarreras($carreras, $extension);

        // verifica que retorne un arreglo vacio
        $this->assertSame([], $resultado);
    }

    private function makeEstudianteData(array $overrides = []): array
    {
        return array_merge([
            'carnet' => 201700841,
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //listarPorCarreras

    public function testCarreras(): void
    {
        $carreras = [1, 3];

        $extension = 1;

        $carnets = [201700841, 201700842];

        // define los estudiantes esperados
        $estudiantes = $this->makeEstudianteData([201700842]);

        // verifica que se obtengan los carnets de las carreras
        $this->carreraEstudianteTable->expects($this->once())
            ->method('getCarnetsPorCarrera')
            // verifica que reciba las carreras correctas
            ->with($carreras)
            // define el valor que devolvera el mock
            ->willReturn($carnets);

        // verifica que se consulten los estudiantes
        $this->studentTable->expects($this->once())
            ->method('fetchByCarnets')
            // verifica que reciba los parametros correctos
            ->with($carnets, $extension, $carreras)
            // define el valor que devolvera el mock
            ->willReturn($estudiantes);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService
            ->listarPorCarreras($carreras, $extension);

        // verifica que el resultado sea el esperado
        $this->assertSame($estudiantes, $resultado);
    }

    //listarPorExtension

    public function testExtension(): void
    {
        // define la extension a consultar
        $extension = 1;

        // define los estudiantes esperados
        $estudiantes = $this->makeEstudianteData([201700842]);

        // verifica que fetchbyextension sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('fetchByExtension')
            // verifica que reciba la extension correcta
            ->with($extension)
            // define el valor que devolvera el mock
            ->willReturn($estudiantes);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService
            ->listarPorExtension($extension);

        // verifica que el resultado sea el esperado
        $this->assertSame($estudiantes, $resultado);
    }

    //buscarPorTipo

    public function testBuscarNoExiste(): void
    {
        $query = 'juan';

        // define el tipo de usuario
        $tipoId = 2;

        $carreras = [1, 2];

        $extension = 1;

        // verifica que se consulte la existencia del estudiante
        $this->studentTable->expects($this->once())
            ->method('existePorNombreOCarnet')
            // verifica que reciba el valor correcto
            ->with($query)
            // define que el estudiante no existe
            ->willReturn(false);

        // verifica que no se realice una busqueda de estudiantes
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnet');

        // verifica que no se obtengan carnets por carrera
        $this->carreraEstudianteTable->expects($this->never())
            ->method('getCarnetsPorCarrera');

        // verifica que no se busquen estudiantes por carrera
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnetYCarnets');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->buscarPorTipo(
            $query,
            $tipoId,
            $carreras,
            $extension
        );

        // verifica el resultado esperado
        $this->assertSame(
            [
                'estudiantes' => [],
                'mensaje' => 'El estudiante no existe.'
            ],
            $resultado
        );
    }

    public function testBuscarAsTipo2(): void
    {
        $query = 'juan';

        // define el tipo personal administrativo
        $tipoId = 2;

        $carreras = [1, 2];

        $extension = 1;

        // define los estudiantes esperados
        $estudiantes = $this->makeEstudianteData([201700842]);

        // verifica que se consulte la existencia del estudiante
        $this->studentTable->expects($this->once())
            ->method('existePorNombreOCarnet')
            // verifica que reciba el valor correcto
            ->with($query)
            // define que el estudiante existe
            ->willReturn(true);

        // verifica que se realice la busqueda para administrador
        $this->studentTable->expects($this->once())
            ->method('buscarPorNombreOCarnet')
            // verifica que reciba los parametros correctos
            ->with($query, $extension)
            // define el valor que devolvera el mock
            ->willReturn($estudiantes);

        // verifica que no se consulten carreras
        $this->carreraEstudianteTable->expects($this->never())
            ->method('getCarnetsPorCarrera');

        // verifica que no se use la busqueda por carreras
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnetYCarnets');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->buscarPorTipo(
            $query,
            $tipoId,
            $carreras,
            $extension
        );

        // verifica el resultado esperado
        $this->assertSame(
            [
                'estudiantes' => $estudiantes,
                'mensaje' => null
            ],
            $resultado
        );
    }

    public function testBuscarSinCarreras(): void
    {
        $query = 'juan';

        // define un tipo distinto de administrador
        $tipoId = 4;

        $carreras = [];

        $extension = 1;

        // verifica que se consulte la existencia del estudiante
        $this->studentTable->expects($this->once())
            ->method('existePorNombreOCarnet')
            // verifica que reciba el valor correcto
            ->with($query)
            // define que el estudiante existe
            ->willReturn(true);

        // verifica que no se consulten carnets por carrera
        $this->carreraEstudianteTable->expects($this->never())
            ->method('getCarnetsPorCarrera');

        // verifica que no se realice la busqueda filtrada
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnetYCarnets');

        // verifica que no se realice la busqueda de administrador
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnet');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->buscarPorTipo(
            $query,
            $tipoId,
            $carreras,
            $extension
        );

        // verifica el resultado esperado
        $this->assertSame(
            [
                'estudiantes' => [],
                'mensaje' => 'No tiene carreras asignadas.'
            ],
            $resultado
        );
    }

    public function testBuscarOtraCarrera(): void
    {
        $query = 'Juan';

        // define un tipo distinto de personal administrativo
        $tipoId = 4;

        $carreras = [1];

        $extension = 1;

        // define los carnets obtenidos por carrera
        $carnets = [201700841, 201700842];

        // verifica que se consulte la existencia del estudiante
        $this->studentTable->expects($this->once())
            ->method('existePorNombreOCarnet')
            // verifica que reciba el valor correcto
            ->with($query)
            // define que el estudiante existe
            ->willReturn(true);

        // verifica que se obtengan los carnets de las carreras
        $this->carreraEstudianteTable->expects($this->once())
            ->method('getCarnetsPorCarrera')
            // verifica que reciba las carreras correctas
            ->with($carreras)
            // define el valor que devolvera el mock
            ->willReturn($carnets);

        // verifica que se realice la busqueda filtrada
        $this->studentTable->expects($this->once())
            ->method('buscarPorNombreOCarnetYCarnets')
            // verifica que reciba los parametros correctos
            ->with($query, $carnets, $carreras, $extension)
            // define que no se encontraron estudiantes
            ->willReturn([]);

        // verifica que no se use la busqueda de administrador
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnet');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->buscarPorTipo(
            $query,
            $tipoId,
            $carreras,
            $extension
        );

        // verifica el resultado esperado
        $this->assertSame(
            [
                'estudiantes' => [],
                'mensaje' => 'El estudiante existe, pero pertenece a otra carrera.'
            ],
            $resultado
        );
    }

    public function testBuscarCarrera(): void
    {
        // define el texto de busqueda
        $query = 'juan';

        // define un tipo distinto de personal administrativo
        $tipoId = 4;

        // define las carreras
        $carreras = [1];

        // define la extension
        $extension = 1;

        // define los carnets obtenidos por carrera
        $carnets = [201700841, 201700842];

        // define los estudiantes encontrados
        $estudiantes = $this->makeEstudianteData([201700842]);

        // verifica que se consulte la existencia del estudiante
        $this->studentTable->expects($this->once())
            ->method('existePorNombreOCarnet')
            // verifica que reciba el valor correcto
            ->with($query)
            // define que el estudiante existe
            ->willReturn(true);

        // verifica que se obtengan los carnets de las carreras
        $this->carreraEstudianteTable->expects($this->once())
            ->method('getCarnetsPorCarrera')
            // verifica que reciba las carreras correctas
            ->with($carreras)
            // define el valor que devolvera el mock
            ->willReturn($carnets);

        // verifica que se realice la busqueda filtrada
        $this->studentTable->expects($this->once())
            ->method('buscarPorNombreOCarnetYCarnets')
            // verifica que reciba los parametros correctos
            ->with($query, $carnets, $carreras, $extension)
            // define los estudiantes encontrados
            ->willReturn($estudiantes);

        // verifica que no se use la busqueda de administrador
        $this->studentTable->expects($this->never())
            ->method('buscarPorNombreOCarnet');

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->buscarPorTipo(
            $query,
            $tipoId,
            $carreras,
            $extension
        );

        // verifica el resultado esperado
        $this->assertSame(
            [
                'estudiantes' => $estudiantes,
                'mensaje' => null
            ],
            $resultado
        );
    }

    //existePorCarnet

    public function testExisteTrue(): void
    {
        // define el carnet a buscar
        $carnet = 201700842;

        // crea el estudiante esperado
        
        // verifica que getbycarnet sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('getByCarnet')
            // verifica que reciba el carnet correcto
            ->with($carnet)
            // define que el estudiante existe
            ->willReturn($this->student);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->existePorCarnet($carnet);

        // verifica que el estudiante exista
        $this->assertTrue($resultado);
    }

    public function testExisteFalse(): void
    {
        // define el carnet a buscar
        $carnet = 201700842;

        // verifica que getbycarnet sea llamado una vez
        $this->studentTable->expects($this->once())
            ->method('getByCarnet')
            // verifica que reciba el carnet correcto
            ->with($carnet)
            // define que el estudiante no existe
            ->willReturn(null);

        // ejecuta el metodo a probar
        $resultado = $this->estudianteService->existePorCarnet($carnet);

        // verifica que el estudiante no exista
        $this->assertFalse($resultado);
    }
}

/*

vendor/bin/phpunit module/Application/test/Service/EstudianteServiceTest.php

*/