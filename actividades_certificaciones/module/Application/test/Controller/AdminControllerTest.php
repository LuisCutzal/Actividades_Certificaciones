<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\AdminService;
use Application\Controller\AdminController;
use Application\Model\RolTable;
use Application\Service\ActivityService;
use Application\Service\UserService;
use Application\Model\ExtensionTable;
use Application\Controller\Plugin\AuthPlugin;
use Application\Service\AuditService;
use Application\Model\User;
use Laminas\Router\Http\RouteMatch;

use Laminas\Db\Adapter\Adapter;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

use Laminas\ServiceManager\PluginManagerInterface;

class AdminControllerTest extends AbstractHttpControllerTestCase
{
    private AdminService $adminService;
    private RolTable $rolTable;
    private ActivityService $activityService;
    private ExtensionTable $extensionTable;
    private PluginManagerInterface $pluginManager;
    private Adapter $adapter;

    private UserService $userService;
    private AuditService $auditService;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->adminService = $this->createMock(AdminService::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->activityService = $this->createMock(ActivityService::class);
        $this->extensionTable = $this->createMock(ExtensionTable::class);
        $this->adapter = $this->createMock(Adapter::class);
        $this->userService = $this->createMock(UserService::class);
        $this->auditService = $this->createMock(AuditService::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';
        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        // 👇 servicios mock
        $serviceManager->setService(AdminService::class, $this->adminService);
        $serviceManager->setService(RolTable::class, $this->rolTable);
        $serviceManager->setService(ActivityService::class, $this->activityService);
        $serviceManager->setService(AuditService::class, $this->auditService);
        $serviceManager->setService(ExtensionTable::class, $this->extensionTable);
        $serviceManager->setService(Adapter::class, $this->adapter);
        $serviceManager->setService(UserService::class, $this->userService);

        // 🔥 IMPORTANTE: ControllerManager override REAL
        $controllers = $serviceManager->get('ControllerManager');
        $controllers->setAllowOverride(true);

        // 🚨 CLAVE: factory que usa ServiceManager (NO mocks sueltos)
        $controllers->setFactory(AdminController::class, function ($container) {
            return new AdminController(
                $container->get(AdminService::class),
                $container->get(RolTable::class),
                $container->get(ActivityService::class),
                $container->get(ExtensionTable::class),
                $container->get(AuditService::class)
            );
        });

        // plugin auth
        $this->pluginManager = $serviceManager->get('ControllerPluginManager');

        $this->mockUsuario();

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn([
                (object)[
                    'extension' => 1,
                    'nombre' => 'Central'
                ]
            ]);

        // limpiar route
        $this->getApplication()
            ->getMvcEvent()
            ->setRouteMatch(new RouteMatch([]));
    }

    private function mockUsuario(
        int $id = 1,
        int $tipoId = 1,
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
                'nombre' => 'Admin Test',
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

    private function helperActivity(
        int $id = 1,
        int $statusCode = 200
    ): void {
        $this->mockUsuario(tipoId: $id);
        // Datos controlados
        $this->activityService
            ->method('getConteoActividadesPorEstado')
            ->willReturn([
                'pendientes' => 4,
                'aprobadas' => 5,
                'rechazadas' => 1
            ]);

        // Los demás métodos pueden devolver vacío
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

        //$this->dispatch('/admin');
        $this->dispatch('/admin/dashboard-data');
        // Validar status
        $this->assertResponseStatusCode($statusCode);
    }

    //validaremos que todo esta cargando correctamente ademas de validar el conteo de actividades por estado
    public function testDashboardDataUsuarioAutorizado(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->activityService
            ->method('getConteoActividadesPorEstado')
            ->willReturn([
                'pendientes' => 4,
                'aprobadas' => 5,
                'rechazadas' => 1
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

        $this->dispatch('/admin/dashboard-data');

        $this->assertResponseStatusCode(200);

        $result = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $result->getVariables();

        $this->assertEquals(10, $data['kpis']['total']);
        $this->assertEquals(5, $data['kpis']['aprobadas']);
        $this->assertEquals(4, $data['kpis']['pendientes']);
        $this->assertEquals(1, $data['kpis']['rechazadas']);
    }

    public function testDashboardUsuarioNoAutorizado(): void
    {
        // Simular usuario SIN permisos (rol distinto)
        $this->helperActivity(2, 302);
        $this->assertRedirect();
    }

    public function testDashboardCalculaTotalCorrectamente(): void
    {
        $this->helperActivity(1, 200);

        $result = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $result->getVariables();

        $this->assertEquals(
            10,
            $data['kpis']['total']
        );
    }

    public function testDashboardRetornaEstructuraCorrecta(): void
    {
        $this->helperActivity(1, 200);

        $result = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $result->getVariables();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('actividadesPorMes', $data);
        $this->assertArrayHasKey('aprobadasVsPendientes', $data);
        $this->assertArrayHasKey('topActividades', $data);
        $this->assertArrayHasKey('topUsuarios', $data);
        $this->assertArrayHasKey('actividadesPorCarrera', $data);

        $this->assertArrayHasKey('total', $data['kpis']);
        $this->assertArrayHasKey('aprobadas', $data['kpis']);
        $this->assertArrayHasKey('pendientes', $data['kpis']);
        $this->assertArrayHasKey('rechazadas', $data['kpis']);
    }

    private function helperRolExtension()
    {
        $roles = new \ArrayObject([
            (object)['id' => 1, 'nombre' => 'Admin']
        ]);

        $extensiones = new \ArrayObject([
            (object)['extension' => 1, 'nombre' => 'Central'],
            (object)['extension' => 2, 'nombre' => 'Xela']
        ]);

        $this->rolTable
            ->method('getRol')
            ->willReturn($roles);

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn($extensiones);
    }

    public function testAddUserUsuarioNoAutorizado(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin

        $this->dispatch('/admin/crearUsuario');

        $this->assertResponseStatusCode(302);
    }

    public function testAddUserPostExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->helperRolExtension();

        $this->adminService
            ->expects($this->once())
            ->method('createUserAsAdmin')
            ->willReturn(1);

        $postData = [
            'nombre' => 'Luis',
            'apellido' => 'Lopez',
            'correo' => 'luis@example.com'
        ];

        $this->dispatch(
            '/admin/crearUsuario',
            'POST',
            $postData
        );

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEquals(
            'Usuario creado correctamente',
            $data['successMessage']
        );
    }

    public function testAddUserCargaRoles(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->helperRolExtension();

        $this->dispatch('/admin/crearUsuario');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('roles', $data);

        $this->assertEquals(
            'Admin',
            $data['roles'][1]
        );
    }

    public function testAddUserPostConError(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->helperRolExtension();

        $this->adminService
            ->method('createUserAsAdmin')
            ->willThrowException(
                new \Exception('Error al crear usuario')
            );

        $postData = [
            'nombre' => 'Luis',
            'apellido' => 'Lopez',
            'correo' => 'luis@example.com'
        ];

        $this->dispatch(
            '/admin/crearUsuario',
            'POST',
            $postData
        );

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEquals(
            'Error al crear usuario',
            $data['errorMessage']
        );
    }

    //ahhora comienza el test de listarUserAction

    public function testListarUsuariosUsuarioNoAutorizado(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin

        $this->dispatch('/admin/listarUsuario');

        $this->assertResponseStatusCode(302);
    }

    public function testListarUsuariosExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $usuariosMock = [
            (array)[
                'id' => 1,
                'nombre' => 'Luis',
                'apellido' => 'Lopez',
                'correo' => 'luis@example.com',
                'estado' => 1,
                'rol_id' => 1,
            ]
        ];

        $this->adminService
            ->method('obtenerUsuarios')
            ->willReturn($usuariosMock);

        $this->helperRolExtension();

        $this->dispatch('/admin/listarUsuario');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        // Validar estructura
        $this->assertArrayHasKey('usuarios', $data);
        $this->assertArrayHasKey('roles', $data);
    }

    // aqui comienza el test de findUserAction

    public function testFindUserSinQueryRedirige(): void
    {
        $this->dispatch('/admin/usuarios/buscar');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/listarUsuario');
    }

    public function testFindUserExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $usuarioMock = [
            (array)[
                'id' => 1,
                'nombre' => 'Luis',
                'apellido' => 'Lopez',
                'correo' => 'luis@example.com',
                'estado' => 1,
                'rol_id' => 1,
                'rol_nombre' => 'Admin',
                'cui' => 1234567890101,
            ]
        ];

        $this->adminService
            ->method('buscarUsuarios')
            ->willReturn($usuarioMock);

        $this->dispatch('/admin/usuarios/buscar?q=1234567890101');

        $this->assertResponseStatusCode(200);
        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('usuarios', $data);
        $this->assertArrayHasKey('query', $data);

        $this->assertEquals(
            '1234567890101',
            $data['query']
        );
    }

    public function testFindUserSinResultados(): void
    {
        $this->adminService
            ->method('buscarUsuarios')
            ->willReturn([]);

        $this->dispatch('/admin/usuarios/buscar?q=999');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEmpty($data['usuarios']);
    }

    //ahora comienza el test de editarUserAction


    public function testEditarUserNoAutorizado(): void
    {
        $this->mockUsuario(tipoId: 99);

        $this->dispatch('/usuario/editar/4');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarUserSinIdRedirige(): void
    {
        $this->mockUsuario(tipoId: 1);
        $this->helperRolExtension();

        $this->dispatch('/usuario/editar/0');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarUserUsuarioNoExiste(): void
    {
        $this->mockUsuario(tipoId: 1);
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
        $this->mockUsuario(tipoId: 1);
        $this->helperRolExtension();

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

        $this->helperRolExtension();

        $this->userService
            ->method('obtenerUsuarioPorId')
            ->willReturn($usuarioMock);


        $this->dispatch('/usuario/editar/1');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('usuario', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('extensiones', $data);
    }

    public function testEditarUserPostExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);
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

        $this->userService
            ->expects($this->once())
            ->method('editUser');

        $this->helperRolExtension();
        $postData = [
            'nombre' => 'Luis Modificado'
        ];
        $this->dispatch(
            '/usuario/editar/1',
            'POST',
            $postData
        );

        $this->assertResponseStatusCode(302);
    }

    public function testEditarUserPostConError(): void
    {
        $this->mockUsuario(tipoId: 1);

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
            ['nombre' => '656']
        );
        $this->assertResponseStatusCode(200);
        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey(
            'errorMessage',
            $data
        );
    }

    //ahora comienza el test de eliminarUserAction

    public function testeliminarUserAction(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin
        $this->helperRolExtension();

        $this->dispatch('/admin/usuarios/eliminar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testEliminarUserSinIdRedirige(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->dispatch('/admin/usuarios/eliminar/1');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/admin/listarUsuario');
    }

    public function testEliminarUserExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $userMock = new User();
        $userMock->id = 1;
        $userMock->cui = 1234567890101;

        $this->adminService
            ->method('getUserById')
            ->willReturn($userMock);

        $this->adminService
            ->expects($this->once())
            ->method('eliminarUsuarioAsAdmin')
            ->with(1);

        //$this->dispatch('/admin/usuarios/eliminar/1');
        $this->dispatch(
            '/admin/usuarios/eliminar/1',
            'POST'
        );

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/admin/listarUsuario');
    }

    //ahora comienza el test de activarUserAction

    public function testactivarUserAction(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin
        $this->helperRolExtension();

        //$this->dispatch('/admin/usuarios/activar/1');
        $this->dispatch(
            '/admin/usuarios/activar/1',
            'POST'
        );

        $this->assertResponseStatusCode(302);
    }

    public function testActivarUserSinIdRedirige(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->dispatch('/admin/usuarios/activar/1');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/admin/listarUsuario');
    }

    public function testActivarUserExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $userMock = new User();
        $userMock->id = 1;
        $userMock->cui = 1234567890101;

        $this->adminService
            ->method('getUserById')
            ->willReturn($userMock);

        $this->adminService
            ->expects($this->once())
            ->method('activarUsuarioAsAdmin')
            ->with(1);

        $this->dispatch('/admin/usuarios/activar/1');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/admin/listarUsuario');
    }

    //ahora comienza el test de  crearRolAction

    public function testCrearRolUsuarioNoAutorizado(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin
        $this->helperRolExtension();

        $this->dispatch('/admin/roles/crear');

        $this->assertResponseStatusCode(302);
    }

    public function testCrearRolPostExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->adminService
            ->method('getPermisos')
            ->willReturn([]);

        $this->adminService
            ->method('getCarreras')
            ->willReturn([]);

        $this->adminService
            ->method('getTipos')
            ->willReturn([]);

        $this->adminService
            ->expects($this->once())
            ->method('crearRol');

        $postData = [
            'nombre' => 'Nuevo Rol',
            'tipo_id' => 2,
            'extension' => 1,
            'permisos' => [1, 2],
            'carreras' => [1, 2]
        ];

        $this->dispatch(
            '/admin/roles/crear',
            'POST',
            $postData
        );

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertEquals(
            'Rol creado correctamente.',
            $data['successMessage']
        );
    }

    //ahora comeinzan los test para listarRolesAction

    public function testListarRolesUsuarioNoAutorizado()
    {
        $this->mockUsuario(tipoId: 2);

        $this->dispatch('/admin/roles/listar');

        $this->assertResponseStatusCode(302);
    }

    public function testListarRolesExitoso()
    {
        $this->mockUsuario(tipoId: 1);

        $rolesMock = [
            (object)[
                'id' => 1,
                'nombre' => 'Administrador',
                'tipo_id' => 1
            ]
        ];

        $this->helperRolExtension();

        $this->adminService
            ->method('obtenerRoles')
            ->willReturn($rolesMock);

        $this->dispatch('/admin/roles/listar');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();
        $this->assertArrayHasKey('roles', $data);
    }

    //ahora comienza el test de la funcion findRolAction

    public function testFindRolSinQueryRedirige(): void
    {
        $this->dispatch('/admin/roles/buscar');

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/listarRoles');
    }

    public function testFinRolExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $rolesMock = [
            (object)[
                'id' => 1,
                'nombre' => 'Administrador',
                'tipo_id' => 1
            ]
        ];

        $this->adminService
            ->method('buscarRoles')
            ->willReturn($rolesMock);

        $this->dispatch('/admin/roles/buscar?q=Administrador');

        $this->assertResponseStatusCode(200);
        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('query', $data);

        $this->assertEquals(
            'Administrador',
            $data['query']
        );
    }

    //ahora comienza el test de modificarRolAction


    public function testEditarRolNoAutorizado(): void
    {
        $this->mockUsuario(tipoId: 2); // No admin

        $this->dispatch('/admin/roles/modificar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarRolSinIdRedirige(): void
    {
        $this->mockUsuario(tipoId: 1);
        $this->helperRolExtension();

        $this->dispatch('/admin/roles/modificar/0');

        $this->assertResponseStatusCode(302);
    }

    public function testEditarRolNoExiste(): void
    {
        $this->mockUsuario(tipoId: 1);

        $this->adminService
            ->method('obtenerRolPorId')
            ->willReturn(null);

        $this->dispatch('/admin/roles/modificar/1');

        $this->assertResponseStatusCode(302);
    }

    public function testModificarRolGetExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $rolMock = (object)[
            'id' => 1,
            'nombre' => 'Administrador',
            'tipo_id' => 1,
            'extension' => 1
        ];

        $this->adminService
            ->method('obtenerRolPorId')
            ->willReturn($rolMock);

        $this->adminService
            ->method('getPermisos')
            ->willReturn([]);

        $this->adminService
            ->method('getPermisosPorRol')
            ->willReturn([]);

        $this->adminService
            ->method('getCarreras')
            ->willReturn([]);

        $this->adminService
            ->method('getCarrerasPorRol')
            ->willReturn([]);

        $this->dispatch('/admin/roles/modificar/1');

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey('rol', $data);
    }

    public function testModificarRolPostExitoso(): void
    {
        $this->mockUsuario(tipoId: 1);

        $rolMock = (object)[
            'id' => 1,
            'nombre' => 'Administrador',
            'tipo_id' => 1,
            'extension' => 1
        ];

        $this->adminService
            ->method('obtenerRolPorId')
            ->willReturn($rolMock);

        $this->adminService
            ->method('getPermisos')
            ->willReturn([]);

        $this->adminService
            ->method('getPermisosPorRol')
            ->willReturn([]);

        $this->adminService
            ->method('getCarreras')
            ->willReturn([]);

        $this->adminService
            ->method('getCarrerasPorRol')
            ->willReturn([]);

        $this->adminService
            ->expects($this->once())
            ->method('modificarRol');

        $this->dispatch(
            '/admin/roles/modificar/1',
            'POST',
            [
                'nombre' => 'Nuevo Nombre',
                'extension' => 1,
                'permisos' => [1, 2],
                'carreras' => [1]
            ]
        );

        $this->assertResponseStatusCode(302);
    }

    public function testModificarRolPostConError(): void
    {
        $this->mockUsuario(tipoId: 1);

        $rolMock = (object)[
            'id' => 1,
            'nombre' => 'Administrador',
            'tipo_id' => 1,
            'extension' => 1
        ];

        $this->adminService
            ->method('obtenerRolPorId')
            ->willReturn($rolMock);

        $this->adminService
            ->method('getPermisos')
            ->willReturn([]);

        $this->adminService
            ->method('getPermisosPorRol')
            ->willReturn([]);

        $this->adminService
            ->method('getCarreras')
            ->willReturn([]);

        $this->adminService
            ->method('getCarrerasPorRol')
            ->willReturn([]);

        $this->adminService
            ->expects($this->once())
            ->method('modificarRol')
            ->willThrowException(
                new \Exception('Error al modificar rol')
            );

        $this->dispatch(
            '/admin/roles/modificar/1',
            'POST',
            // [
            //     'nombre' => 'Nuevo Nombre'
            // ]
            [
                'nombre' => 'Nuevo Nombre',
                'extension' => 1,
                'permisos' => [1],
                'carreras' => [1]
            ]
        );

        $this->assertResponseStatusCode(200);

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $data = $viewModel->getVariables();

        $this->assertArrayHasKey(
            'errorMessage',
            $data
        );
    }
}
/*
vendor/bin/phpunit module/Application/test/Controller/AdminControllerTest.php

*/