<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\ExtensionTable;
use Application\Model\RolTable;
use Application\Controller\BaseController;
use Laminas\Db\Adapter\Adapter;
use Application\Controller\Plugin\AuthPlugin;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\ServiceManager\PluginManagerInterface;


class BaseControllerTest extends AbstractHttpControllerTestCase
{
    private ExtensionTable $extensionTable;
    private RolTable $rolTable;
    private PluginManagerInterface $pluginManager;
    private Adapter $adapter;
    private TestBaseController $controller;
    private RouteMatch $routeMatch;
    private MvcEvent $event;
    private Request $request;

    protected function setUp(): void
    {
        $this->extensionTable =
            $this->createMock(ExtensionTable::class);

        $this->rolTable =
            $this->createMock(RolTable::class);

        $this->adapter =
            $this->createMock(Adapter::class);

        $config =
            include __DIR__
            . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager =
            $this->getApplicationServiceLocator();

        $serviceManager->setAllowOverride(true);

        // ==========================
        // Registrar services mockeados
        // ==========================

        $serviceManager->setService(
            ExtensionTable::class,
            $this->extensionTable
        );

        $serviceManager->setService(
            RolTable::class,
            $this->rolTable
        );

        $serviceManager->setService(
            Adapter::class,
            $this->adapter
        );

        // ==========================
        // Controller base de prueba
        // ==========================

        $this->controller =
            new TestBaseController(
                $this->extensionTable,
                $this->rolTable
            );

        $serviceManager->setService(
            BaseController::class,
            $this->controller
        );

        // ==========================
        // Plugin Manager
        // ==========================

        $this->pluginManager =
            $serviceManager->get(
                'ControllerPluginManager'
            );

        // ==========================
        // Route + Router + Event
        // ==========================

        $this->routeMatch =
            new RouteMatch([]);

        $config =
            $serviceManager->get('config');

        $router =
            TreeRouteStack::factory(
                $config['router'] ?? []
            );

        $request = new Request();

        $this->request = $request;

        $this->event = new MvcEvent();

        $this->event->setRouter($router);

        $this->event->setRouteMatch(
            $this->routeMatch
        );

        $this->event->setRequest($request);


        // ==========================
        // Mock conexión DB
        // ==========================

        $connectionMock =
            $this->createMock(
                ConnectionInterface::class
            );

        $connectionMock
            ->method('beginTransaction');

        $connectionMock
            ->method('commit');

        $connectionMock
            ->method('rollback');

        $driverMock =
            $this->createMock(
                DriverInterface::class
            );

        $driverMock
            ->method('getConnection')
            ->willReturn($connectionMock);

        $this->adapter
            ->method('getDriver')
            ->willReturn($driverMock);

        // ==========================
        // Redirect mock
        // ==========================

        $response = new Response();

        $redirect =
            $this->createMock(
                Redirect::class
            );

        $redirect
            ->method('toRoute')
            ->willReturn($response);

        $redirect
            ->method('toUrl')
            ->willReturn($response);

        $this->pluginManager
            ->setService(
                'redirect',
                $redirect
            );

        // ==========================
        // FlashMessenger mock
        // ==========================

        $flashMessenger =
            $this->createMock(
                FlashMessenger::class
            );

        $flashMessenger
            ->method('addErrorMessage');

        $this->pluginManager
            ->setService(
                'flashMessenger',
                $flashMessenger
            );
    }

    public function testCheckAuthUserNotLoggedIn()
    {
        // Mock AuthPlugin
        $authPlugin = $this->createMock(AuthPlugin::class);

        $authPlugin
            ->method('requireLogin')
            ->willReturn(false);


        // Mock Redirect
        $response = new Response();

        $redirect =
            $this->createMock(Redirect::class);

        $redirect
            ->method('toRoute')
            ->willReturn($response);

        // Registrar plugins correctamente
        $this->pluginManager
            ->setService('authPlugin', $authPlugin);



        // Crear controller
        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller
            ->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );



        // Ejecutar método
        $result = $controller->publicCheckAuth();

        // Verificar resultado
        $this->assertInstanceOf(
            Response::class,
            $result
        );
    }


    public function testCheckAuthTipoNoPermitido()
    {
        $authPlugin = $this->createMock(AuthPlugin::class);

        $authPlugin
            ->method('requireLogin')
            ->willReturn(true);

        $authPlugin
            ->method('getUser')
            ->willReturn([
                'tipo_id' => 2,
                'permisos' => []
            ]);

        $this->pluginManager
            ->setService('authPlugin', $authPlugin);

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $result = $controller->publicCheckAuth([1]);

        $this->assertInstanceOf(
            Response::class,
            $result
        );
    }

    public function testCheckAuthSinPermiso()
    {
        $authPlugin = $this->createMock(AuthPlugin::class);

        $authPlugin
            ->method('requireLogin')
            ->willReturn(true);

        $authPlugin
            ->method('getUser')
            ->willReturn([
                'tipo_id' => 1,
                'permisos' => ['ver']
            ]);

        $this->pluginManager
            ->setService('authPlugin', $authPlugin);

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $result = $controller->publicCheckAuth(
            [1],
            'editar'
        );

        $this->assertInstanceOf(
            Response::class,
            $result
        );
    }

    public function testCheckAuthSuccess()
    {
        $userData = [
            'tipo_id' => 1,
            'permisos' => ['crear-actividad']
        ];

        $authPlugin = $this->createMock(AuthPlugin::class);

        $authPlugin
            ->method('requireLogin')
            ->willReturn(true);

        $authPlugin
            ->method('getUser')
            ->willReturn($userData);

        $this->pluginManager
            ->setService('authPlugin', $authPlugin);

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $result = $controller->publicCheckAuth(
            [1],
            'crear-actividad'
        );

        $this->assertEquals(
            $userData,
            $result
        );
    }

    public function testHandleActivityCreationSuccess()
    {
        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $createFunction = function ($data) {
            return true;
        };

        $view = $controller->publicHandleActivityCreation(
            $createFunction
        );

        $this->assertInstanceOf(
            \Laminas\View\Model\ViewModel::class,
            $view
        );
    }

    //test para el handleUserEdit

    public function testHandleUserEditWithoutIdRedirects()
    {
        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $getUserById = function ($id) {
            return null;
        };

        $updateUser = function ($data) {};

        $result = $controller->publicHandleUserEdit(
            $getUserById,
            $updateUser,
            '/usuarios',
            '/usuarios/list'
        );

        $this->assertInstanceOf(
            \Laminas\Http\Response::class,
            $result
        );
    }

    public function testHandleUserEditUserNotFound()
    {
        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $this->routeMatch->setParam('id', 5);

        $getUserById = function ($id) {
            return null;
        };

        $updateUser = function ($data) {};

        $result = $controller->publicHandleUserEdit(
            $getUserById,
            $updateUser,
            '/usuarios',
            '/usuarios/list'
        );

        $this->assertInstanceOf(
            \Laminas\Http\Response::class,
            $result
        );
    }

    public function testHandleUserEditGetShowsForm()
    {
        $usuarioFake = (object)[
            'id' => 1,
            'nombre' => 'Juan'
        ];

        $this->routeMatch->setParam('id', 1);

        $this->rolTable
            ->method('getRol')
            ->willReturn([]);

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn([]);

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->getEvent()->setRequest(
            $this->request
        );


        $getUserById = function ($id) use ($usuarioFake) {
            return $usuarioFake;
        };

        $updateUser = function ($data) {};

        $result = $controller->publicHandleUserEdit(
            $getUserById,
            $updateUser,
            '/usuarios',
            '/usuarios/list'
        );

        $this->assertInstanceOf(
            \Laminas\View\Model\ViewModel::class,
            $result
        );

        $this->assertEquals(
            'application/usuario/editar-user',
            $result->getTemplate()
        );
    }

    public function testHandleUserEditPostSuccess()
    {
        $usuarioFake = (object)[
            'id' => 1
        ];

        $this->routeMatch->setParam('id', 1);

        $request = new Request();
        $request->setMethod('POST');
        $request->getPost()->fromArray([
            'nombre' => 'Nuevo'
        ]);

        // ==========================
        // Mock authPlugin
        // ==========================

        $authPlugin = $this->createMock(AuthPlugin::class);

        $authPlugin
            ->method('getUser')
            ->willReturn([
                'tipo_id' => 1
            ]);

        $this->pluginManager
            ->setService('authPlugin', $authPlugin);

        $this->rolTable
            ->method('getRol')
            ->willReturn([]);

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn([]);

        $updateCalled = false;

        $getUserById = function ($id) use ($usuarioFake) {
            return $usuarioFake;
        };

        $updateUser = function ($data) use (&$updateCalled) {
            $updateCalled = true;
        };

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->setTestRequest($request);

        $result = $controller->publicHandleUserEdit(
            $getUserById,
            $updateUser,
            '/usuarios',
            '/usuarios/list'
        );

        $this->assertTrue($updateCalled);

        $this->assertInstanceOf(
            \Laminas\Http\Response::class,
            $result
        );
    }

    public function testHandleUserEditPostWithError()
    {
        $usuarioFake = (object)[
            'id' => 1,
            'nombre' => 'Juan'
        ];

        $this->routeMatch->setParam('id', 1);

        $request = new Request();
        $request->setMethod('POST');
        $request->getPost()->fromArray([
            'nombre' => 'Error'
        ]);
        $this->event->setRequest($request);

        $this->rolTable
            ->method('getRol')
            ->willReturn([]);

        $this->extensionTable
            ->method('fetchAll')
            ->willReturn([]);

        $getUserById = function ($id) use ($usuarioFake) {
            return $usuarioFake;
        };

        $updateUser = function ($data) {
            throw new \Exception('Error al actualizar');
        };

        $controller = new TestBaseController(
            $this->extensionTable,
            $this->rolTable
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEvent($this->event);

        $controller->setTestRequest($request);


        $result = $controller->publicHandleUserEdit(
            $getUserById,
            $updateUser,
            '/usuarios',
            '/usuarios/list'
        );

        $this->assertInstanceOf(
            \Laminas\View\Model\ViewModel::class,
            $result
        );

        $this->assertEquals(
            'Error al actualizar',
            $result->getVariable('errorMessage')
        );
    }
}


//porque las funciones de base controller son protected debemos de hacerlo de esta forma
class TestBaseController extends BaseController
{
    public function publicCheckAuth(
        ?array $tiposPermitidos = null,
        ?string $permisoAccion = null
    ) {
        return $this->checkAuth(
            $tiposPermitidos,
            $permisoAccion
        );
    }

    public function publicHandleUserEdit(
        callable $getUserById,
        callable $updateUser,
        string $redirectUrlIfNoId,
        string $redirectUrlAfterUpdate
    ) {
        return $this->handleUserEdit(
            $getUserById,
            $updateUser,
            $redirectUrlIfNoId,
            $redirectUrlAfterUpdate
        );
    }

    public function publicHandleActivityCreation(
        callable $createFunction
    ) {
        return $this->handleActivityCreation(
            $createFunction
        );
    }

    private ?Request $testRequest = null; //Puede ser Request o puede ser null al inicio

    public function setTestRequest(Request $request)
    {
        // var_dump($request);
        // exit;
        $this->testRequest = $request;
    }

    public function getRequest()
    {
        return $this->testRequest ?? parent::getRequest();
    }
}

/*

vendor/bin/phpunit module/Application/test/Controller/BaseControllerTest.php

*/