<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\AttendanceService;
use Application\Service\ActivityService;
use Laminas\Db\Adapter\Adapter;
use Application\Controller\AttendanceController;
use Application\Controller\Plugin\AuthPlugin;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\PluginManagerInterface;

class AttendanceControllerTest extends AbstractHttpControllerTestCase
{

    private AttendanceService $attendanceService;
    private ActivityService $activityService;
    private PluginManagerInterface $pluginManager;
    private Adapter $adapter;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->activityService = $this->createMock(ActivityService::class);
        $this->adapter = $this->createMock(Adapter::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();

        $serviceManager->setAllowOverride(true);

        $serviceManager->setService(AttendanceService::class, $this->attendanceService);
        $serviceManager->setService(ActivityService::class, $this->activityService);
        $serviceManager->setService(Adapter::class, $this->adapter);

        $controller = new AttendanceController(
            $this->attendanceService,
            $this->activityService
        );


        $serviceManager->setService(
            AttendanceController::class,
            $controller
        );

        $this->pluginManager = $serviceManager
            ->get('ControllerPluginManager');

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
        int $id = 1,
        int $tipoId = 2,
        array $permisos = [1, 2, 3],
        int $rolId = 1,
        array $carreras = [],
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

    public function testViewUsuarioNoAutorizado()
    {
        $this->mockUsuario(tipoId: 1);

        $this->dispatch('/actividades');

        $this->assertResponseStatusCode(302);
    }

    public function testViewSinListaRedirige(): void
    {
        $this->mockUsuario(tipoId: 2);

        // Simular que no hay lista
        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn(null);

        $this->dispatch('/actividades/126/asistencia');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/actividades');
    }

    public function testViewActividadNoExisteRedirige(): void
    {
        $this->mockUsuario(tipoId: 2);

        $listaMock = (object)[
            'id' => 1,
            'estado' => 1
        ];

        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn($listaMock);

        $this->attendanceService
            ->method('obtenerDetalleLista')
            ->willReturn([]);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn(null);

        $this->dispatch('/actividades/126/asistencia');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/actividades');
    }

    public function testViewGetExitoso(): void
    {
        $this->mockUsuario(tipoId: 2);

        $listaMock = (object)[
            'id' => 1,
            'estado' => 1
        ];

        $actividadMock = (object)[
            'id' => 126,
            'estado' => 1
        ];

        $this->attendanceService
            ->method('obtenerListaPorActividad')
            ->willReturn($listaMock);

        $this->attendanceService
            ->method('obtenerDetalleLista')
            ->willReturn([]);

        $this->activityService
            ->method('obtenerActividadPorId')
            ->willReturn($actividadMock);

        $this->dispatch('/actividades/126/asistencia');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('estudiantes', $data);
    }
}


/*

vendor/bin/phpunit module/Application/test/Controller/AttendanceControllerTest.php

*/