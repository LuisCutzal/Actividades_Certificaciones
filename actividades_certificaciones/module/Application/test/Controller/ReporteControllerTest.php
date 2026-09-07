<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\ReporteService;
use Application\Model\ReportePdfTable;
use Application\Model\StudentTable;
use Application\Model\PlantillasPDFTable;
use Application\Service\AuditService;
use Application\Model\Student;

use Laminas\Db\Adapter\Adapter;
use Application\Controller\Plugin\AuthPlugin;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\PluginManagerInterface;

class ReporteControllerTest extends AbstractHttpControllerTestCase
{
    private ReporteService $reporteService;
    private ReportePdfTable $reportePdfTable;
    private StudentTable $studentTable;
    private PlantillasPDFTable $plantillasPDFTable;
    private AuditService $auditService;

    private Adapter $adapter;
    private PluginManagerInterface $pluginManager;


    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->reporteService = $this->createMock(ReporteService::class);
        $this->reportePdfTable = $this->createMock(ReportePdfTable::class);
        $this->studentTable = $this->createMock(StudentTable::class);
        $this->plantillasPDFTable = $this->createMock(PlantillasPDFTable::class);
        $this->auditService = $this->createMock(AuditService::class);

        $this->adapter = $this->createMock(Adapter::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $serviceManager->setService(ReporteService::class, $this->reporteService);
        $serviceManager->setService(ReportePdfTable::class, $this->reportePdfTable);
        $serviceManager->setService(StudentTable::class, $this->studentTable);
        $serviceManager->setService(PlantillasPDFTable::class, $this->plantillasPDFTable);
        $serviceManager->setService(AuditService::class, $this->auditService);
        $serviceManager->setService(Adapter::class, $this->adapter);

        $this->pluginManager = $serviceManager
            ->get('ControllerPluginManager');
        $this->mockUsuario();

        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock->method('beginTransaction');
        $connectionMock->method('commit');
        $connectionMock->method('rollback');

        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock->method('getConnection')
            ->willReturn($connectionMock);

        $this->adapter
            ->method('getDriver')
            ->willReturn($driverMock);
    }

    private function mockUsuario(
        int $id = 99,
        int $tipoId = 4, //tipo 2 o 4 no importa para el test
        array $permisos = [],
        int $rolId = 2,
        array $carreras = [1],
        int $extension = 1,
        int $cui = 1234567890101,
        int $carnet = 20230001,
    ): void {

        $authPluginMock = $this->createMock(AuthPlugin::class);

        $authPluginMock
            ->expects($this->any())
            ->method('requireLogin')
            ->willReturn(true);

        $authPluginMock
            ->expects($this->any())
            ->method('getUser')
            ->willReturn([
                'id' => $id,
                'nombre' => 'Usuario Test',
                'apellido' => 'Apellido Test',
                'cui' => $cui,
                'rol_id' => $rolId,
                'extension' => $extension,
                'tipo_id' => $tipoId,
                'carreras' => $carreras,
                'permisos' => $permisos,
                'carnet' => $carnet
            ]);

        $this->pluginManager->setAllowOverride(true);

        $this->pluginManager->setService(
            'authPlugin',
            $authPluginMock
        );
    }


    public function testActividadesActionReturnsViewModelWithActividades(): void
    {

        $this->mockUsuario(tipoId: 1);

        $actividadesMock = [
            (object)[
                'id' => 1,
                'actividad' => 'Actividad Test',
                'nombre' => 'Actividad Nombre',
                'organizador_nombre' => 'Organizador Test',
                'estado' => 'Aprobada'
            ]
        ];

        $this->reporteService
            ->expects($this->once())
            ->method('reporteActividades')
            ->with(
                1,      // tipo_id mockUsuario()
                [1],    // carreras
                1,      // extension
                2       // rol_id
            )
            ->willReturn($actividadesMock);

        $this->dispatch('/reportes/actividades');



        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');
        $this->assertControllerName(\Application\Controller\ReporteController::class);
        $this->assertControllerClass('ReporteController');
        $this->assertMatchedRouteName('reportes/actividades');

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey('actividades', $variables);
        $this->assertEquals('', $variables['buscar']);
    }

    public function testActividadesActionUsesBuscarActividadesWhenBuscarExists(): void
    {
        $this->mockUsuario(tipoId: 1);

        $actividadesMock = [
            (object)[
                'id' => 1,
                'actividad' => 'Actividad Buscada',
                'nombre' => 'Actividad Nombre',
                'organizador_nombre' => 'Organizador Test',
                'estado' => 'Aprobada'
            ]
        ];

        $this->reporteService
            ->expects($this->once())
            ->method('buscarActividades')
            ->with('test')
            ->willReturn($actividadesMock);

        $this->reporteService
            ->expects($this->never())
            ->method('reporteActividades');

        $this->dispatch('/reportes/actividades?buscar=test');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey('actividades', $variables);

        $this->assertEquals('test', $variables['buscar']);
    }

    //test para actividadAction

    public function testActividadActionReturnsActividad(): void
    {
        $this->mockUsuario(tipoId: 1);

        $actividadMock = (object)[
            'id' => 10,
            'nombre' => 'Actividad Test',
            'estado' => 'Aprobada',
            'tipo_participacion' => 'participante',
            'credito' => 5,
            'fecha' => '2025-01-01'
        ];

        $this->reporteService
            ->expects($this->once())
            ->method('reporteActividad')
            ->with(10)
            ->willReturn($actividadMock);

        $this->dispatch('/reportes/actividad/10');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');
        $this->assertControllerName(\Application\Controller\ReporteController::class);
        $this->assertControllerClass('ReporteController');

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey('actividad', $variables);

        $this->assertEquals(
            'Actividad Test',
            $variables['actividad']->nombre
        );
    }

    public function testActividadPdfActionReturns404WhenActividadDoesNotExist(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->reporteService
            ->expects($this->once())
            ->method('reporteActividad')
            ->with(999)
            ->willReturn(null);

        $this->reportePdfTable
            ->expects($this->never())
            ->method('insert');

        $this->auditService
            ->expects($this->never())
            ->method('log');

        $this->dispatch('/reportes/actividad/999/pdf');

        $this->assertResponseStatusCode(404);
    }

    public function testActividadParticipantesPdfActionRedirectsWhenNoParticipants(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->reporteService
            ->expects($this->once())
            ->method('reporteParticipantesActividad')
            ->with(10)
            ->willReturn([]);

        $this->reportePdfTable
            ->expects($this->never())
            ->method('insert');

        $this->auditService
            ->expects($this->never())
            ->method('log');

        $this->dispatch('/reportes/actividad/10/participantes/pdf');

        $this->assertResponseStatusCode(302);

        $headers = $this->getResponse()->getHeaders();

        $this->assertNotNull(
            $headers->get('Location')
        );
    }

    public function testActividadParticipantesPdfActionGeneratesPdf(): void
    {
        $this->mockUsuario(tipoId: 1);

        $participantesMock = [
            (object)[
                'actividad_id' => 15,
                'actividad' => 'Actividad Participantes',
                'actividad_nombre' => 'Actividad Participantes',
                'estado' => 'Aprobada',
                'nombre' => 'Juan Pérez',
                'carnet' => '20230001',
                'tipo_participacion' => 'Asistente',
                'estado_lista' => 'Aprobado'
            ]
        ];

        $year = (int) date('Y');

        $this->reporteService
            ->expects($this->once())
            ->method('reporteParticipantesActividad')
            ->with(15)
            ->willReturn($participantesMock);

        $this->reportePdfTable
            ->expects($this->once())
            ->method('generarCorrelativo')
            ->with('ACT-PART', $year);

        $this->reportePdfTable
            ->expects($this->once())
            ->method('insert');

        $this->auditService
            ->expects($this->once())
            ->method('log');

        $this->dispatch('/reportes/actividad/15/participantes/pdf');

        $this->assertResponseStatusCode(200);

        $headers = $this->getResponse()->getHeaders();

        $this->assertEquals(
            'application/pdf',
            $headers->get('Content-Type')->getFieldValue()
        );

        $this->assertStringContainsString(
            'lista_estudiantes_actividad_15.pdf',
            $headers->get('Content-Disposition')->getFieldValue()
        );
    }

    //auditoriaEstudiantesAction

    public function testAuditoriaEstudiantesActionReturnsFilteredStudents(): void
    {
        $this->mockUsuario(tipoId: 1);

        $estudiantesMock = [
            (object)[
                'carnet' => '20230001',
                'nombre' => 'Juan Pérez',
                'cui' => '1234567890101',

                'estado_lista' => 'Aprobado',
                'estudiante_nombre' => 'Juan Pérez',
                'actividad_nombre' => 'Actividad Test',
                'creado' => '2025-01-01 10:00:00',
                'organizador_nombre' => 'Organizador Test',
                'carrera_nombre' => 'Ingeniería',
                'tipo_participacion' => 'Asistente',
                'credito' => 1,
                'fecha_actividad' => '2025-01-10',
                'aprobado' => 'Administrador',
                'rechazado' => null,
                'fecha_resolucion' => '2025-01-15',
                'motivo_rechazo' => null,
            ]
        ];

        $this->reporteService
            ->expects($this->once())
            ->method('reporteAuditoriaEstudiantes')
            ->with('20230001')
            ->willReturn($estudiantesMock);

        $this->dispatch(
            '/reportes/auditoria/estudiantes?carnet=20230001'
        );

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerName(
            \Application\Controller\ReporteController::class
        );

        $this->assertMatchedRouteName(
            'reportes/auditoria-estudiantes'
        );

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getViewModel();

        $children = $viewModel->getChildren();

        $variables = $children[0]->getVariables();

        $this->assertArrayHasKey('estudiantes', $variables);

        $this->assertEquals(
            '20230001',
            $variables['carnet']
        );

        $this->assertEquals(
            ['carnet' => '20230001'],
            $variables['query']
        );
    }

    public function testAuditoriaEstudiantesActionRedirectsWhenUserHasNoPermission(): void
    {
        // Usuario tipo 4 (no permitido)
        $this->mockUsuario(tipoId: 4);

        $this->reporteService
            ->expects($this->never())
            ->method('reporteAuditoriaEstudiantes');

        $this->dispatch('/reportes/auditoria/estudiantes');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/');

        $this->assertModuleName('application');

        $this->assertControllerName(
            \Application\Controller\ReporteController::class
        );
    }

    public function testActividadActionReturns404WhenIdIsMissing(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->reporteService
            ->expects($this->never())
            ->method('reporteActividad');

        $this->dispatch('/reportes/actividad');

        $this->assertResponseStatusCode(404);
    }

    public function testAuditoriaEstudiantesActionWithoutCarnet(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->reporteService
            ->expects($this->once())
            ->method('reporteAuditoriaEstudiantes')
            ->with('')
            ->willReturn([]);

        $this->dispatch('/reportes/auditoria/estudiantes');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey('estudiantes', $variables);

        $this->assertNull($variables['carnet']);

        $this->assertEquals(
            ['carnet' => null],
            $variables['query']
        );
    }

    public function testActividadPdfActionRedirectsWhenUserHasNoPermission(): void
    {
        // Usuario no autorizado
        $this->mockUsuario(tipoId: 4);

        $this->reporteService
            ->expects($this->never())
            ->method('reporteActividad');

        $this->reportePdfTable
            ->expects($this->never())
            ->method('insert');

        $this->auditService
            ->expects($this->never())
            ->method('log');

        $this->dispatch('/reportes/actividad/1/pdf');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/');

        $this->assertModuleName('application');

        $this->assertControllerName(
            \Application\Controller\ReporteController::class
        );
    }

    public function testActividadActionReturns404WhenActividadDoesNotExist(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->reporteService
            ->expects($this->once())
            ->method('reporteActividad')
            ->with(999)
            ->willReturn(null);

        $this->dispatch('/reportes/actividad/999');

        $this->assertResponseStatusCode(404);

        $this->assertModuleName('application');

        $this->assertControllerName(
            \Application\Controller\ReporteController::class
        );
    }

    // auditoriaAction

    public function testAuditoriaActionReturnsStudentsForAssociation(): void
    {
        $this->mockUsuario(
            tipoId: 4,
            rolId: 10
        );

        $estudiantes = [
            (object)[
                'estado_lista' => 1,
                'estudiante_nombre' => 'Juan Pérez',
                'actividad_nombre' => 'Actividad Test',
                'organizador_nombre' => 'Organizador Test',
                'carrera_nombre' => 'Ingeniería',
                'tipo_participacion' => 'participante',
                'credito' => 2,
                'fecha_actividad' => '2025-01-01',
                'creado' => '2025-01-02 10:00:00',
                'aprobado' => 1,
                'rechazado' => 0,
                'motivo_rechazo' => null,
                'carnet' => '20230001',
            ]
        ];

        $this->reporteService
            ->expects($this->once())
            ->method('fetchAuditoriaEstudiantesByOrganizacion')
            ->with(
                $this->callback(fn($u) => $u['rol_id'] === 10),
                ''
            )
            ->willReturn($estudiantes);

        $this->dispatch('/reportes/auditoria/asociacion');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey('estudiantes', $variables);

        $this->assertEquals('', $variables['carnet']);

        $this->assertInstanceOf(
            \Laminas\Paginator\Paginator::class,
            $variables['estudiantes']
        );
    }

    

    //historialAction
    public function testHistorialActionReturnsViewWithReportes(): void
    {
        $this->mockUsuario(tipoId: 1, rolId: 10);

        $reportes = [
            (object)[
                'correlativo' => 'EST-2025-0001',
                'tipo' => 3,
                'estudiante_nombre' => 'Mock',
                'actividad_nombre' => 'Mock',
                'generado_por_nombre' => 'Mock',
                'rol_generador_nombre' => 'Mock',
            ],
        ];

        $this->reportePdfTable
            ->expects($this->once())
            ->method('buscarPorCorrelativo')
            ->with('EST')
            ->willReturn($reportes);

        $this->dispatch('/reportes/historial?q=EST');

        $this->assertResponseStatusCode(200);

        $view = $this->getApplication()
            ->getMvcEvent()
            ->getResult()
            ->getVariables();

        $this->assertArrayHasKey('reportes', $view);
        $this->assertEquals('EST', $view['buscar']);
    }
}



/*

vendor/bin/phpunit module/Application/test/Controller/ReporteControllerTest.php

*/