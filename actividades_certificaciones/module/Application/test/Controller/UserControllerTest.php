<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\UserService;
use Application\Service\ActivityService;
use Application\Model\ExtensionTable;
use Application\Model\RolTable;

use Laminas\Db\Adapter\Adapter;
use Application\Controller\Plugin\AuthPlugin;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\PluginManagerInterface;

class UserControllerTest extends AbstractHttpControllerTestCase
{
    private UserService $userService;
    private ActivityService $activityService;
    private ExtensionTable $extensionTable;
    private RolTable $rolTable;

    private Adapter $adapter;
    private PluginManagerInterface $pluginManager;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userService = $this->createMock(UserService::class);
        $this->activityService = $this->createMock(ActivityService::class);
        $this->extensionTable = $this->createMock(ExtensionTable::class);
        $this->rolTable = $this->createMock(RolTable::class);

        $this->adapter = $this->createMock(Adapter::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $serviceManager->setService(UserService::class, $this->userService);
        $serviceManager->setService(ActivityService::class, $this->activityService);
        $serviceManager->setService(ExtensionTable::class, $this->extensionTable);
        $serviceManager->setService(RolTable::class, $this->rolTable);
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

    public function testDashboardActionReturnsViewModelWhenAuthorized(): void
    {
        // mock del plugin auth
        $this->mockUsuario(tipoId: 4);

        // forzamos auth plugin
        $this->dispatch('/dashboard', 'GET');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()->getMvcEvent()->getResult();

        $this->assertInstanceOf(\Laminas\View\Model\ViewModel::class, $viewModel);
        $this->assertEquals('application/usuario/dashboard', $viewModel->getTemplate());

        $variables = $viewModel->getVariables();
        $this->assertEquals(4, $variables['tipo_id']);
    }

    private function mockUsuarioNoAutorizado(): void
    {
        $authPluginMock = $this->createMock(AuthPlugin::class);

        $authPluginMock
            ->method('requireLogin')
            ->willReturn(false);

        $authPluginMock
            ->method('getUser')
            ->willReturn(null);

        $this->pluginManager->setAllowOverride(true);

        $this->pluginManager->setService(
            'authPlugin',
            $authPluginMock
        );
    }

    public function testDashboardActionRedirectsWhenUnauthorized(): void
    {
        $this->mockUsuarioNoAutorizado();

        $this->dispatch('/dashboard', 'GET');

        $this->assertResponseStatusCode(302);
    }

    public function testDashboardDataActionReturnsJson(): void
    {
        $this->mockUsuario(tipoId: 4);

        $this->mockDashboardServices();

        $this->dispatch('/dashboard-data', 'GET');

        $this->assertResponseStatusCode(200);

        $json = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals(6, $json['kpis']['total']);
        $this->assertEquals(3, $json['kpis']['aprobadas']);
        $this->assertEquals(2, $json['kpis']['pendientes']);
        $this->assertEquals(1, $json['kpis']['rechazadas']);

        $this->assertEquals(['mock' => 'mes'], $json['actividadesPorMes']);
    }

    private function mockDashboardServices(): void
    {
        $this->activityService
            ->expects($this->once())
            ->method('getConteoActividadesPorEstado')
            ->willReturn([
                'pendientes' => 2,
                'aprobadas' => 3,
                'rechazadas' => 1,
            ]);

        $this->activityService
            ->expects($this->once())
            ->method('getActividadesPorMes')
            ->willReturn(['mock' => 'mes']);

        $this->activityService
            ->expects($this->once())
            ->method('getAprobadasVsPendientes')
            ->willReturn(['mock' => 'ap_vs_pen']);

        $this->activityService
            ->expects($this->once())
            ->method('getTopActividades')
            ->willReturn(['mock' => 'top_act']);

        $this->activityService
            ->expects($this->once())
            ->method('getTopUsuarios')
            ->willReturn(['mock' => 'top_users']);

        $this->activityService
            ->expects($this->once())
            ->method('getActividadesPorCarrera')
            ->willReturn(['mock' => 'carrera']);
    }

    public function testDashboardDataActionRedirectsWhenUnauthorized(): void
    {
        $this->mockUsuarioNoAutorizado();

        $this->dispatch('/dashboard-data', 'GET');

        $this->assertResponseStatusCode(302);
    }

    public function testDashboardDataActionSendsCorrectFilters(): void
    {
        $this->mockUsuario(tipoId: 4);

        $expectedFilters = [
            'year' => '2025',
            'monthStart' => '1',
            'monthEnd' => '6',
            'dateStart' => null,
            'dateEnd' => null,
        ];

        $this->activityService
            ->expects($this->once())
            ->method('getConteoActividadesPorEstado')
            ->willReturn([
                'pendientes' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0,
            ]);

        $this->activityService
            ->expects($this->once())
            ->method('getActividadesPorMes')
            ->with(
                $this->anything(),
                $expectedFilters
            )
            ->willReturn([]);

        $this->activityService
            ->method('getAprobadasVsPendientes')
            ->willReturn([]);

        $this->activityService
            ->method('getTopActividades')
            ->willReturn([]);

        $this->activityService
            ->method('getTopUsuarios')
            ->willReturn([]);

        $this->activityService
            ->method('getActividadesPorCarrera')
            ->willReturn([]);

        $this->dispatch(
            '/dashboard-data?year=2025&monthStart=1&monthEnd=6',
            'GET'
        );

        $this->assertResponseStatusCode(200);
    }

    public function testDashboardDataActionHandlesEmptyKpis(): void
    {
        $this->mockUsuario(tipoId: 4);

        $this->activityService
            ->method('getConteoActividadesPorEstado')
            ->willReturn([
                'pendientes' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0,
            ]);

        $this->activityService
            ->method('getActividadesPorMes')
            ->willReturn([]);

        $this->activityService
            ->method('getAprobadasVsPendientes')
            ->willReturn([]);

        $this->activityService
            ->method('getTopActividades')
            ->willReturn([]);

        $this->activityService
            ->method('getTopUsuarios')
            ->willReturn([]);

        $this->activityService
            ->method('getActividadesPorCarrera')
            ->willReturn([]);

        $this->dispatch('/dashboard-data', 'GET');

        $json = json_decode(
            $this->getResponse()->getContent(),
            true
        );

        $this->assertEquals(0, $json['kpis']['total']);
    }

    public function testDashboardDataActionContainsExpectedKeys(): void
    {
        $this->mockUsuario(tipoId: 4);

        $this->mockDashboardServices();

        $this->dispatch('/dashboard-data', 'GET');

        $json = json_decode($this->getResponse()->getContent(), true);

        $this->assertArrayHasKey('kpis', $json);
        $this->assertArrayHasKey('actividadesPorMes', $json);
        $this->assertArrayHasKey('aprobadasVsPendientes', $json);
        $this->assertArrayHasKey('topActividades', $json);
        $this->assertArrayHasKey('topUsuarios', $json);
        $this->assertArrayHasKey('actividadesPorCarrera', $json);
    }

    //editarUserAction

    private function helperRolExtension()
    {
        $roles = new \ArrayObject([
            (object)['id' => 1, 'nombre' => 'Usuario']
        ]);

        $extensiones = new \ArrayObject([
            (object)['extension' => 1, 'nombre' => 'Central']
        ]);

        $this->rolTable
            ->method('getRol')
            ->willReturn($roles);

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn($extensiones);
    }

    public function testEditarUserSinIdRedirige(): void
    {
        $this->mockUsuario(tipoId: 4);
        $this->helperRolExtension();

        $this->dispatch('/usuario/editar/0');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarUserUsuarioNoExiste(): void
    {
        $this->mockUsuario(tipoId: 4);
        //usare userService en lugar de adminService ya que adminservice no busca al usuario, es userservice quien lo hace
        $this->userService
            ->expects($this->once())
            ->method('obtenerUsuarioPorId')
            ->with(1)
            ->willReturn(null);

        $this->helperRolExtension();

        $this->dispatch('/usuario/editar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarUserGetExitoso(): void
    {
        $this->mockUsuario(tipoId: 4);

        $usuarioMock = (object)[
            'id' => 1,
            'nombre' => 'Luis',
            'apellido' => 'Lopez',
            'correo' => 'luis@example.com',
            'estado' => 1,
            'rol_id' => 1,
            'rol_nombre' => 'Admin',
            'cui' => 1234567890101,
            'extension' => 1
        ];

        $this->userService
            ->method('obtenerUsuarioPorId')
            ->willReturn($usuarioMock);

        $this->helperRolExtension();

        $this->dispatch('/usuario/editar/1');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEquals(
            'application/usuario/editar-user',
            $viewModel->getTemplate()
        );

        $this->assertFalse($data['esAdmin']);

        $this->assertArrayHasKey('usuario', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('extensiones', $data);
    }

    public function testEditarUserPostExitoso(): void
    {
        $this->mockUsuario(tipoId: 4);

        $usuarioMock = (object)[
            'id' => 1,
            'nombre' => 'Luis',
        ];

        $this->userService
            ->method('obtenerUsuarioPorId')
            ->willReturn($usuarioMock);

        $this->userService
            ->expects($this->once())
            ->method('editUser')
            ->with($this->callback(function ($data) {
                return
                    $data['id'] === 1
                    && $data['password'] === 'cambio'
                    && $data['password2'] === 'cambio';
            }));

        $this->helperRolExtension();

        $postData = [
            'password' => 'cambio',
            'password2' => 'cambio'
        ];

        $this->dispatch(
            '/usuario/editar/1',
            'POST',
            $postData
        );

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/actividades');
    }

    public function testEditarUserPostConError(): void
    {
        $this->mockUsuario(tipoId: 4);

        $usuarioMock = (object)[
            'id' => 1,
            'nombre' => 'Luis',
            'apellido' => 'Lopez',
            'correo' => 'luis@example.com',
            'estado' => 1,
            'rol_id' => 1,
            'rol_nombre' => 'Asociacion de Arquitectura',
            'cui' => 1234567890101,
            'extension' => 1,
            'carnet' => 123456789
        ];

        $this->userService
            ->method('obtenerUsuarioPorId')
            ->willReturn($usuarioMock);

        $this->userService
            ->expects($this->once())
            ->method('editUser')
            ->willThrowException(
                new \Exception('Error al editar usuario')
            );

        $this->helperRolExtension();

        $this->dispatch(
            '/usuario/editar/1',
            'POST',
            [
                'password' => 'abc',
                'password2' => 'xyz'
            ]
        );

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEquals(
            'Error al editar usuario',
            $data['errorMessage']
        );

        $this->assertFalse($data['esAdmin']);
    }
}


/*

vendor/bin/phpunit module/Application/test/Controller/UserControllerTest.php

*/