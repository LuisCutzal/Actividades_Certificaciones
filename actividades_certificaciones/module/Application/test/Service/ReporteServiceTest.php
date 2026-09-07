<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Service\ActivityService;
use Application\Service\EstudianteService;
use Application\Service\ReporteService;

use Application\Model\StudentTable;
use Application\Model\Student;
use Application\Model\Carrera;

use Application\Model\CarreraTable;
use Application\Model\RolTable;


class ReporteServiceTest extends TestCase
{
    private ActivityService $activityService;
    private EstudianteService $estudianteService;
    private ReporteService $reporteService;
    private StudentTable $studentTable;
    private Student $student;
    private CarreraTable $carreraTable;
    private Carrera $carrera;
    private RolTable $rolTable;

    protected function setUp(): void
    {
        $this->activityService = $this->createMock(ActivityService::class);
        $this->studentTable = $this->createMock(StudentTable::class);
        $this->estudianteService = $this->createMock(EstudianteService::class);
        $this->carreraTable = $this->createMock(CarreraTable::class);
        $this->student = $this->createMock(Student::class);
        $this->carrera = $this->createMock(Carrera::class);
        $this->rolTable = $this->createMock(RolTable::class);

        $this->reporteService = new ReporteService(
            $this->activityService,
            $this->studentTable,
            $this->estudianteService,
            $this->carreraTable,
            $this->rolTable
        );
    }

    //reporteActividades

    public function testReporteActividades(): void
    {
        // define el tipo de actividad
        $tipoId = 1;

        // define las carreras del usuario
        $userCarreras = [1, 2];

        // define la extension del usuario
        $userExtension = 1;

        // define el rol del usuario
        $rolId = 3;

        // define el resultado esperado del servicio
        $expected = ['actividad1', 'actividad2'];

        // se verifica que el metodo listarActividades sea llamado una vez
        $this->activityService->expects($this->once())
            // se especifica el metodo a interceptar
            ->method('listarActividades')
            // se validan los parametros enviados
            ->with($tipoId, $userCarreras, $userExtension, $rolId)
            // se define lo que debe retornar el mock
            ->willReturn($expected);

        // se ejecuta el metodo del reporte service
        $result = $this->reporteService->reporteActividades(
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );

        // se valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //buscarActividades

    public function testBuscarActividades(): void
    {
        // define el texto de busqueda
        $buscar = 'evento';

        // define el resultado esperado
        $expected = ['actividad1', 'actividad2'];

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('buscarActividades')
            // valida el parametro enviado
            ->with($buscar)
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->buscarActividades($buscar);

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //reporteActividad

    public function testReporteActividad(): void
    {
        // define el id de la actividad
        $id = 10;

        // define el resultado esperado
        $expected = ['actividad'];

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('obtenerActividadReporteById')
            // valida el parametro enviado
            ->with($id)
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->reporteActividad($id);

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //reporteParticipantesActividad

    public function testRepParticipantesActividad(): void
    {
        // define el id de la actividad
        $actividadId = 5;

        // define el resultado esperado
        $expected = ['participante1', 'participante2'];

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('obtenerParticipantesPorActividad')
            // valida el parametro enviado
            ->with($actividadId)
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->reporteParticipantesActividad($actividadId);

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }


    //reporteAuditoriaEstudiantes

    public function testRepAuditoria(): void
    {
        // define el carnet a consultar
        $carnet = 201700841;

        // define el resultado esperado
        $expected = ['registro1', 'registro2'];

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('reporteAuditoriaEstudiantes')
            // valida el parametro enviado
            ->with($carnet)
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->reporteAuditoriaEstudiantes($carnet);

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //fetchAuditoriaEstudiantesByOrganizacion

    public function testAuditoriaOrg(): void
    {
        // define los datos del usuario
        $user = [
            'id' => 1,
            'rol' => 2
        ];

        // define el carnet a consultar
        $carnet = 201700841;

        // define el resultado esperado
        $expected = ['registro1', 'registro2'];

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('fetchAuditoriaEstudiantesByOrganizacion')
            // valida los parametros enviados
            ->with($user, $carnet)
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->fetchAuditoriaEstudiantesByOrganizacion(
            $user,
            $carnet
        );

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //fetchPdfEstudiante

    public function testPdfEstudiante(): void
    {
        // define el id del estudiante
        $estudianteId = 1;

        // define el modo de generacion
        $modo = 'aprobadas'; //puede ser tambien "todas" aunque todas traeria a las aprobadas y pendientes

        // define la carrera del usuario
        $carreraUsuario = 2;

        // define la extension del usuario
        $extensionUsuario = 3;

        // define el resultado esperado
        $expected = 'contenido.pdf';

        // verifica que el metodo sea llamado una vez
        $this->activityService->expects($this->once())
            // define el metodo esperado
            ->method('fetchPdfEstudiante')
            // valida los parametros enviados
            ->with(
                $estudianteId,
                $modo,
                $carreraUsuario,
                $extensionUsuario
            )
            // define el valor de retorno
            ->willReturn($expected);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->fetchPdfEstudiante(
            $estudianteId,
            $modo,
            $carreraUsuario,
            $extensionUsuario
        );

        // valida que el resultado sea el esperado
        $this->assertSame($expected, $result);
    }

    //obtenerDatosEstudiante

    public function testDatosEstudiante(): void
    {
        // define el carnet a consultar
        $carnet = 201700841;

        // verifica que el metodo sea llamado una vez
        $this->studentTable->expects($this->once())
            // define el metodo esperado
            ->method('getByCarnet')
            // valida el parametro enviado
            ->with($carnet)
            // define el objeto que retornara el mock
            ->willReturn($this->student);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->obtenerDatosEstudiante($carnet);

        // valida que el objeto retornado sea el esperado
        $this->assertSame($this->student, $result);
    }

    //existePorCarnet

    public function testExisteCarnet(): void
    {
        // define el id del estudiante
        $idEstudiante = 201700841;

        // verifica que el metodo sea llamado una vez
        $this->estudianteService->expects($this->once())
            // define el metodo esperado
            ->method('existePorCarnet')
            // valida el parametro enviado
            ->with($idEstudiante)
            // define el valor de retorno
            ->willReturn(true);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->existePorCarnet($idEstudiante);

        // valida que el resultado sea verdadero
        $this->assertTrue($result);
    }

    //getCarrera

    public function testGetCarrera(): void
    {
        // define el id de la carrera
        $id = 1;

        // verifica que el metodo sea llamado una vez
        $this->carreraTable->expects($this->once())
            // define el metodo esperado
            ->method('getCarrera')
            // valida el parametro enviado
            ->with($id)
            // define el valor de retorno
            ->willReturn($this->carrera);

        // ejecuta el metodo del servicio
        $result = $this->reporteService->getCarrera($id);

        // valida que el resultado sea el esperado
        $this->assertSame($this->carrera, $result);
    }
}


/*

vendor/bin/phpunit module/Application/test/Service/ReporteServiceTest.php

*/