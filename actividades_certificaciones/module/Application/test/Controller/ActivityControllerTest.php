<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\ActivityService;
use Application\Service\AttendanceService;

use Laminas\Db\Adapter\Adapter;
use Application\Model\RolTable;
use Application\Model\CarreraTable;
use Application\Model\ExtensionTable;
use Application\Controller\Plugin\AuthPlugin;
use Application\Model\InscripcionTable;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Service\AuditService;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use Laminas\Db\ResultSet\ResultSet;

class ActivityControllerTest extends AbstractHttpControllerTestCase
{
    private ActivityService $activityService;
    private AttendanceService $attendanceService;
    private AuditService $auditService;
    private CarreraTable $carreraTable;
    private Adapter $adapter;
    private ExtensionTable $extensionTable;
    private RolTable $rolTable;
    private PluginManagerInterface $pluginManager;
    private InscripcionTable $inscripcionTable;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->activityService = $this->createMock(ActivityService::class);
        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->auditService = $this->createMock(AuditService::class);
        $this->carreraTable = $this->createMock(CarreraTable::class);
        $this->adapter = $this->createMock(Adapter::class);
        $this->extensionTable = $this->createMock(ExtensionTable::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->inscripcionTable = $this->createMock(InscripcionTable::class);
        //$this->authPlugin = $this->createMock(AuthPlugin::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();


        // Inyectar mocks en el ServiceManager
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(ActivityService::class, $this->activityService);
        $serviceManager->setService(AttendanceService::class, $this->attendanceService);
        $serviceManager->setService(AuditService::class, $this->auditService);
        $serviceManager->setService(CarreraTable::class, $this->carreraTable);
        $serviceManager->setService(Adapter::class, $this->adapter);
        $serviceManager->setService(ExtensionTable::class, $this->extensionTable);
        $serviceManager->setService(RolTable::class, $this->rolTable);
        $serviceManager->setService(InscripcionTable::class, $this->inscripcionTable);
        //$serviceManager->setService(AuthPlugin::class, $this->authPlugin);

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

    public function testIndexActionListarActividades()
    {
        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $actividadMock = $this->createActividadMock();

        $this->activityService
            ->method('listarActividades')
            ->willReturn([
                $actividadMock
            ]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);

        $this->assertMatchedRouteName('actividades');

        $this->assertControllerClass(
            'activitycontroller'
        );

        $this->assertTemplateName(
            'application/actividad/index'
        );
    }

    public function testIndexActionConBusqueda()
    {
        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $actividadMock = $this->createActividadMock();

        $this->activityService
            ->method('buscarActividad')
            ->willReturn([$actividadMock]);

        $this->dispatch('/actividades?buscar=test');

        $this->assertResponseStatusCode(200);

        $this->assertMatchedRouteName('actividades');

        $this->assertControllerClass('activitycontroller');

        $this->assertTemplateName('application/actividad/index');
    }

    private function createActividadMock(
        int $id = 1,
        string $nombre = 'Actividad Test'
    ) {

        return (object) [
            'id' => $id,
            'nombre' => $nombre,
            'fecha' => '2025-01-01',
            'credito' => 10,
            'estado' => 1,
            'tipo_participacion' => 'individual'
        ];
    }

    public function testIndexActionTipo3Pendientes()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = $this->createActividadMock();

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([$actividadMock]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionListarActividadesUsuarioNormal()
    {
        $this->activityService
            ->expects($this->once())
            ->method('listarActividades')
            ->willReturn([
                $this->createActividadMock()
            ]);

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionSinPermisoRedirige()
    {
        $this->mockUsuario(tipoId: 1);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(302);
    }

    private function mockUsuario(
        int $id = 99,
        int $tipoId = 2,
        array $permisos = [],
        int $rolId = 1,
        array $carreras = [1],
        int $extension = 1
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
                'cui' => '1234567890101',
                'tipo_id' => $tipoId,
                'rol_id' => $rolId,
                'carreras' => $carreras,
                'extension' => $extension,
                'permisos' => $permisos
            ]);

        $this->pluginManager->setAllowOverride(true);

        $this->pluginManager->setService(
            'authPlugin',
            $authPluginMock
        );
    }

    public function testAddActivityGetMuestraFormulario()
    {
        $this->mockUsuario(
            tipoId: 2,
            permisos: ['crear_actividad']
        );

        // Mock roles
        $rolMock = (object)[
            'id' => 1,
            'nombre' => 'Asociación Arquitectura Central'
        ];

        $this->rolTable
            ->method('getRol')
            ->willReturn([$rolMock]);

        // Mock carreras
        $carreraMock = (object)[
            'carrera' => 1,
            'nombre' => 'Licenciatura en Arquitectura'
        ];

        $this->carreraTable
            ->method('fetchAll')
            ->willReturn([$carreraMock]);

        $this->dispatch('/actividades/crear');

        $this->assertResponseStatusCode(200);

        $this->assertTemplateName(
            'application/actividad/add-activity'
        );
    }

    /*
    createActivity()
    crearLista()
    registrarEstudiantesMasivo()
    */

    public function testAddActivityPostTipoLista()
    {
        $this->mockUsuario(
            tipoId: 2,
            permisos: ['crear_actividad'],
            extension: 1
        );

        $inscripcion = new \stdClass();
        $inscripcion->extension = 1;

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->method('current')
            ->willReturn($inscripcion);

        $this->inscripcionTable
            ->method('obtenerCarreraActual')
            ->willReturn($resultSet);

        $this->activityService
            ->method('createActivity')
            ->willReturn(10);

        $this->attendanceService
            ->method('crearLista')
            ->willReturn([
                'listaId' => 5,
                'carnets' => [
                    ['carnet' => 12345]
                ]
            ]);

        $this->attendanceService
            ->expects($this->once())
            ->method('registrarEstudiantesMasivo');


        $this->getRequest()->setMethod('POST');

        $this->getRequest()->getPost()->fromArray([
            'tipo_participacion' => 'lista',
            'carrera' => 1
        ]);

        $this->getRequest()->getFiles()->fromArray([
            'lista_asistencia' => [
                'tmp_name' => '/tmp/test.csv'
            ]
        ]);

        $this->dispatch('/actividades/crear');

        $this->assertResponseStatusCode(302);
    }

    public function testAddActivityPostTipoIndividual()
    {
        $this->mockUsuario(
            tipoId: 2,
            permisos: ['crear_actividad'],
            extension: 1
        );

        $inscripcion = new \stdClass();
        $inscripcion->extension = 1;

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->method('current')
            ->willReturn($inscripcion);

        $this->inscripcionTable
            ->method('obtenerCarreraActual')
            ->willReturn($resultSet);

        $this->activityService
            ->method('createActivity')
            ->willReturn(10);

        $this->attendanceService
            ->method('crearListaVacia')
            ->willReturn(5);

        $this->attendanceService
            ->expects($this->once())
            ->method('registrarEstudianteEnActividad')
            ->with(
                $this->equalTo(10),
                $this->isType('int'),
                $this->equalTo(2),
                $this->equalTo(1),
                $this->equalTo(5),
                $this->equalTo(true)
            );

        $this->getRequest()->setMethod('POST');

        $this->getRequest()->getPost()->fromArray([
            'tipo_participacion' => 'individual',
            'carrera' => 2,
            'estudiante_individual' => 12345
        ]);

        $this->getRequest()->getFiles()->fromArray([]);

        $this->dispatch('/actividades/crear');

        $this->assertResponseStatusCode(302);
    }

    public function testAddActivityPostTipoInvalido()
    {
        $this->mockUsuario(
            tipoId: 2,
            permisos: ['crear_actividad'],
            extension: 1
        );

        $this->activityService
            ->method('createActivity')
            ->willReturn(10);

        $this->adapter
            ->method('getDriver')
            ->willReturn(
                $this->createMock(DriverInterface::class)
            );

        $this->getRequest()->setMethod('POST');

        $this->getRequest()->getPost()->fromArray([
            'tipo_participacion' => null,
            'carrera' => 2
        ]);

        $this->getRequest()->getFiles()->fromArray([]);

        $this->dispatch('/actividades/crear');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/actividades');
    }

    public function testEditarActivityGetMuestraFormulario()
    {
        $this->mockUsuario(
            tipoId: 2,
            permisos: [],
            extension: 1
        );

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 0, //aun no esta aprobada ni rechazada
            'tipo_participacion' => 'individual',
            'nombre' => 'Actividad prueba',
            'organizador' => 'Asociación Arquitectura Central',
            'carrera' => 1,
            'fecha' => '2026-04-17',
            'credito' => 2
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->attendanceService
            ->method('obtenerEstudiantePorActividad')
            ->willReturn(['carnet' => 123]);

        // roles
        $this->rolTable
            ->method('getRol')
            ->willReturn([(object)['id' => 1, 'nombre' => 'Administrador']]);

        // carreras
        $this->carreraTable
            ->method('fetchAll')
            ->willReturn([(object)['carrera' => 1, 'nombre' => 'Licenciatura en Arquitectura']]);

        $this->dispatch('/actividades/edit/1');

        $this->assertResponseStatusCode(200);
    }

    public function testEditarActivitySinIdRedirige()
    {
        $this->mockUsuario(tipoId: 2);

        $this->dispatch('/actividades/edit/0');

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/actividades');
    }

    public function testEditarActivityNoExisteLanzaExcepcion()
    {
        $this->mockUsuario(tipoId: 2);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn(null);


        $this->dispatch('/actividades/edit/1');
        $this->assertResponseStatusCode(500);
    }

    public function testEditarActivityAprobadaLanzaExcepcion()
    {
        $this->mockUsuario(tipoId: 2);

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 1,
            'tipo_participacion' => 'lista'
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->dispatch('/actividades/edit/1');
        $this->assertResponseStatusCode(500);
    }

    //ahora haremos para la funcion updateActivityAction

    public function testUpdateActivitySinIdRedirige()
    {
        $this->mockUsuario(tipoId: 3);

        $this->dispatch('/actividades/aprobar/0');

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/actividades');
    }

    public function testUpdateActivityActividadNoExiste()
    {
        $this->mockUsuario(tipoId: 3);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn(null);
        $this->dispatch('/actividades/aprobar/1');

        $this->assertResponseStatusCode(500);
    }

    public function testUpdateActivityAprobarConLista()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'tipo_participacion' => 'lista'
        ];

        $listaMock = (object)[
            'id' => 10
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->activityService
            ->expects($this->once())
            ->method('ActualizarActividad');

        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn($listaMock);

        $this->attendanceService
            ->expects($this->once())
            ->method('aprobarLista')
            ->with(10);

        $this->dispatch('/actividades/aprobar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testUpdateActivitySinLista()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'tipo_participacion' => 'individual'
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->activityService
            ->expects($this->once())
            ->method('ActualizarActividad');

        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn(null);

        $this->attendanceService
            ->expects($this->never())
            ->method('aprobarLista');

        $this->dispatch('/actividades/aprobar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testUpdateActivityConReferer()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'tipo_participacion' => null
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->activityService
            ->expects($this->once())
            ->method('ActualizarActividad');

        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn(null);

        $request = $this->getRequest();
        $request->getHeaders()->addHeaderLine('Referer', '/dashboard');

        $this->dispatch('/actividades/aprobar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testRechazarActividad()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 0
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->rolTable
            ->method('getRol')
            ->willReturn([
                (object)[
                    'id' => 1,
                    'nombre' => 'Administrador'
                ]
            ]);

        $this->activityService
            ->expects($this->once())
            ->method('RechazarActividad')
            ->with(
                1,
                'Motivo de prueba',
                99
            );

        $postData = [
            'motivo_rechazo' => 'Motivo de prueba'
        ];

        $this->getRequest()->setMethod('POST');
        $this->getRequest()->getPost()->fromArray($postData);

        $this->dispatch('/actividades/rechazar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testRechazarActividadSinMotivo()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 0,
            'nombre' => 'Actividad prueba',
            'organizador' => 'Asociación Arquitectura Central',
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);


        $this->activityService
            ->expects($this->never())
            ->method('RechazarActividad');

        $this->dispatch(
            '/actividades/rechazar/1',
            'POST',
            [
                'motivo_rechazo' => ''
            ]
        );

        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString(
            'Debe ingresar un motivo de rechazo.',
            $this->getResponse()->getContent()
        );
    }

    public function testRechazarActividadEstado2Redirige()
    {
        $this->mockUsuario(tipoId: 3, id: 99);

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 2
        ];

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->activityService
            ->expects($this->never())
            ->method('RechazarActividad');

        $this->dispatch('/actividades/rechazar/1');

        $this->assertResponseStatusCode(302);
    }

    // aca comienzan los test para la funcion ListadoRechazoAction

    public function testListadoRechazo()
    {
        $this->mockUsuario(tipoId: 4);

        $actividadesMock = [
            (object)[
                'id' => 1,
                'estado' => 0, //aun no esta aprobada ni rechazada
                'tipo_participacion' => 'individual',
                'nombre' => 'Actividad prueba',
                'organizador' => 'Asociación Arquitectura Central',
                'carrera' => 1,
                'fecha' => '2026-04-17',
                'credito' => 2,
                'fecha_resolucion' => '2026-05-01',
                'motivo_rechazo' => 'Motivo de rechazo de prueba'
            ]
        ];

        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadesRechazadas')
            ->with(
                4,          // tipo_id
                [1],        // carreras
                1,          // extension
                ''          // búsqueda vacía
            )
            ->willReturn($actividadesMock);

        $this->dispatch('/actividades/rechazadas');

        $this->assertResponseStatusCode(200);

        $this->assertTemplateName('application/actividad/rechazadas');
    }

    public function testListadoRechazoConBusqueda()
    {
        $this->mockUsuario(
            tipoId: 3,
            carreras: [1],
            extension: 1
        );

        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadesRechazadas')
            ->with(
                3,          // tipo_id
                [1],        // carreras
                1,          // extension
                'test'      // búsqueda
            )
            ->willReturn([]);

        $this->rolTable
            ->method('getRol')
            ->willReturn([
                (object)[
                    'id' => 2,
                    'nombre' => 'Personal Administrativo'
                ]
            ]);

        $this->dispatch('/actividades/rechazadas?buscar=test');

        $this->assertResponseStatusCode(200);
    }

    //a continuacion los test para la funcion VerRechazoAction

    public function testVerRechazoActividad()
    {
        $this->mockUsuario(tipoId: 4);

        $actividadMock = (object)[
            'id' => 1,
            'estado' => 2,
            'tipo_participacion' => 'individual',
            'nombre' => 'Actividad prueba',
            'organizador' => 'Asociación Arquitectura Central',
            'carrera' => 1,
            'fecha' => '2026-04-17',
            'credito' => 2,
            'fecha_resolucion' => '2026-05-01',
            'motivo_rechazo' => 'Motivo de rechazo de prueba'
        ];

        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->dispatch('/actividades/ver-rechazo/1');

        $this->assertResponseStatusCode(200);

        $this->assertTemplateName('application/actividad/ver-rechazo');
    }

    public function testVerRechazoActividadNoExiste()
    {
        $this->mockUsuario(tipoId: 4);

        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->willReturn(false);

        $this->dispatch('/actividades/ver-rechazo/1');

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/actividades/rechazadas');
    }

    public function testVerTodasLasActividades()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'nombre' => 'Actividad 1'
        ];

        $this->activityService
            ->method('obtenerTodasLasActividades')
            ->with(
                3,          // tipo_id
                1,          // rol_id
                [1],        // carreras
                1,          // extension
            )
            ->willReturn([$actividadMock]);

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);

        $this->assertTemplateName('application/actividad/index');
    }

    public function testVerTodasLasActividadesError()
    {
        $this->mockUsuario(tipoId: 3);

        $this->activityService
            ->method('obtenerTodasLasActividades')
            ->willThrowException(new \Exception('Error al obtener actividades'));

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(500);
    }

    // test para la funcion ListaAprobadasAction

    public function testListaAprobadas()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'nombre' => 'Actividad 1',
            'estado' => 1
        ];

        $this->activityService
            ->method('obtenerActividadesAprobadas')
            ->willReturn([$actividadMock]);

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);

        $this->assertTemplateName('application/actividad/index');
    }

    public function testListaAprobadasConPendientes()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadAprobada = (object)[
            'id' => 1,
            'nombre' => 'Actividad Aprobada',
            'estado' => 1,
            'fecha' => '2026-05-01',
            'credito' => 1,
            'tipo_participacion' => 'individual'
        ];

        $actividadPendiente = (object)[
            'id' => 0,
            'nombre' => 'Actividad Pendiente',
            'estado' => 0,
            'fecha' => '2026-05-01',
            'credito' => 2,
            'tipo_participacion' => 'individual'
        ];

        $this->activityService
            ->method('obtenerActividadesAprobadas')
            ->willReturn([
                $actividadAprobada
            ]);

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([
                $actividadPendiente
            ]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);
    }

    public function testListaAprobadasSinPermiso()
    {
        $this->mockUsuario(tipoId: 1);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(302);
    }

    public function testListaAprobadasVacia()
    {
        $this->mockUsuario(tipoId: 3);

        $this->activityService
            ->method('obtenerActividadesAprobadas')
            ->willReturn([]);

        $this->activityService
            ->method('obtenerActividadesPendientes')
            ->willReturn([]);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(200);
    }

    //test para la funcion DetalleAction

    public function testDetalleActividad()
    {
        $this->mockUsuario(tipoId: 3);

        $actividadMock = (object)[
            'id' => 1,
            'nombre' => 'Actividad Detalle',
            'estado' => 1,
            'fecha' => '2026-05-01',
            'credito' => 1,
            'tipo_participacion' => 'individual',
            'organizador' => 'Asociación Arquitectura Central',
            'carrera' => 1
        ];

        $this->rolTable
            ->method('getRol')
            ->willReturn([
                (object)[
                    'id' => 3,
                    'nombre' => 'Secretaria'
                ]
            ]);

        $this->carreraTable
            ->method('fetchAll')
            ->willReturn([
                (object)['carrera' => 1, 'nombre' => 'licenciatura en arquitectura']
            ]);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->dispatch('/actividades/detalle/1');

        $this->assertResponseStatusCode(200);
        $this->assertTemplateName('application/actividad/detalle');
    }

    public function testDetalleActividadSinIdRedirige()
    {
        $this->mockUsuario(tipoId: 3);

        $this->dispatch('/actividades/detalle/0');

        $this->assertResponseStatusCode(302);
    }

    public function testDetalleActividadNoExisteRedirige()
    {
        $this->mockUsuario(tipoId: 3);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn(null);

        $this->dispatch('/actividades/detalle/999');

        $this->assertResponseStatusCode(302);
    }

    public function testDetalleActividadSinAuth()
    {
        $this->mockUsuario(tipoId: 1); // no permitido

        $this->dispatch('/actividades/detalle/1');

        $this->assertResponseStatusCode(302);
    }
}

/*

vendor/bin/phpunit module/Application/test/Controller/ActivityControllerTest.php

*/