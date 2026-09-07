<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\EstudianteService;
use Laminas\Db\Adapter\Adapter;
use Application\Controller\Plugin\AuthPlugin;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\PluginManagerInterface;

class EstudianteControllerTest extends AbstractHttpControllerTestCase
{
    private EstudianteService $estudianteService;

    private Adapter $adapter;
    private PluginManagerInterface $pluginManager;


    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->estudianteService = $this->createMock(EstudianteService::class);
        $this->adapter = $this->createMock(Adapter::class);

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(EstudianteService::class, $this->estudianteService);
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
        int $tipoId = 2, //tipo 2 o 4 no importa para el test
        array $permisos = [],
        int $rolId = 2,
        array $carreras = [1],
        int $extension = 1,
        int $cui = 1234567890101,
        int $carnet = 20230001,
        int $registro_personal = 12345,
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
                'carnet' => $carnet,
                'registro_personal' => $registro_personal,
            ]);

        $this->pluginManager->setAllowOverride(true);

        $this->pluginManager->setService(
            'authPlugin',
            $authPluginMock
        );
    }

    //test para el index

    public function testIndexActionConCarreras(): void
    {
        $estudiantes = [
            (object)[
                'carnet' => 20230001,
                'nombre' => 'Usuario Test',
                'anio_inscrito' => 2023,
            ]
        ];

        $this->estudianteService
            ->expects($this->once())
            ->method('listarPorCarreras')
            ->with([1], 1)
            ->willReturn($estudiantes);

        $this->estudianteService
            ->expects($this->never())
            ->method('listarPorExtension');

        $this->dispatch('/estudiantes');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerName(
            'Application\Controller\EstudianteController'
        );

        $this->assertControllerClass('EstudianteController');

        $this->assertMatchedRouteName('estudiantes');

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey(
            'estudiantes',
            $variables
        );

        $this->assertEquals(
            2,
            $variables['tipo_id']
        );
    }

    public function testIndexActionSinCarreras(): void
    {
        // Usuario sin carreras
        $this->mockUsuario(carreras: []);

        $estudiantes = [
            (object)[
                'carnet' => 20230002,
                'nombre' => 'Pedro',
                'anio_inscrito' => 2022,
            ]
        ];

        $this->estudianteService
            ->expects($this->never())
            ->method('listarPorCarreras');

        $this->estudianteService
            ->expects($this->once())
            ->method('listarPorExtension')
            ->with(1)
            ->willReturn($estudiantes);

        $this->dispatch('/estudiantes');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerClass(
            'EstudianteController'
        );

        $this->assertMatchedRouteName('estudiantes');

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey(
            'estudiantes',
            $variables
        );

        $this->assertEquals(
            2,
            $variables['tipo_id']
        );
    }

    public function testBuscarActionConQuery(): void
    {
        $resultado = [
            'estudiantes' => [
                (object)[
                    'carnet' => 20230001,
                    'nombre' => 'Pedro',
                    'anio_inscrito' => 2022,
                ]
            ],
            'mensaje' => null
        ];

        $this->estudianteService
            ->expects($this->once())
            ->method('buscarPorTipo')
            ->with(
                'Pedro',
                2,
                [1],
                1
            )
            ->willReturn($resultado);

        $this->dispatch('/estudiantes/buscar?q=Pedro');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerClass(
            'EstudianteController'
        );

        $this->assertMatchedRouteName(
            'estudiantes/buscar'
        );

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertEquals(
            'Pedro',
            $variables['query']
        );

        $this->assertArrayHasKey(
            'estudiantes',
            $variables
        );

        $this->assertEquals(
            2,
            $variables['tipo_id']
        );
    }

    public function testBuscarActionSinQuery(): void
    {
        $this->estudianteService
            ->expects($this->never())
            ->method('buscarPorTipo');

        $this->dispatch('/estudiantes/buscar');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerClass(
            'EstudianteController'
        );

        $this->assertMatchedRouteName(
            'estudiantes/buscar'
        );

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertEquals(
            '',
            $variables['query']
        );

        $this->assertArrayHasKey(
            'estudiantes',
            $variables
        );

        $this->assertEquals(
            [],
            $variables['estudiantes']
        );

        $this->assertNull(
            $variables['mensaje']
        );

        $this->assertEquals(
            2,
            $variables['tipo_id']
        );
    }

    public function testVerActionEstudianteNoEncontrado(): void
    {
        $this->estudianteService
            ->expects($this->once())
            ->method('obtenerEstudiante')
            ->with(20230001)
            ->willReturn(null);

        $this->dispatch('/estudiantes/ver/20230001');

        $this->assertResponseStatusCode(500);

        $error = $this->getApplication()
            ->getMvcEvent()
            ->getParam('exception');

        $this->assertInstanceOf(
            \Exception::class,
            $error
        );

        $this->assertEquals(
            'Estudiante no encontrado',
            $error->getMessage()
        );
    }

    public function testVerActionExitosa(): void
    {
        $estudiante = (object)[
            'carnet' => 20230001,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
        ];

        $actividades = [
            (object)[
                'actividad_nombre' => 'Seminario',
                'fecha' => '2025-01-01',
                'credito' => 1,
                'estado' => 'Aprobado',
            ]
        ];

        $this->estudianteService
            ->expects($this->once())
            ->method('obtenerEstudiante')
            ->with(20230001)
            ->willReturn($estudiante);

        $this->estudianteService
            ->expects($this->once())
            ->method('obtenerActividadesDelEstudiante')
            ->with(20230001, '')
            ->willReturn($actividades);

        $this->dispatch('/estudiantes/ver/20230001');

        $this->assertResponseStatusCode(200);

        $this->assertModuleName('application');

        $this->assertControllerClass(
            'EstudianteController'
        );

        $this->assertMatchedRouteName(
            'estudiantes/ver'
        );

        $viewModel = $this->getApplication()
            ->getMvcEvent()
            ->getResult();

        $variables = $viewModel->getVariables();

        $this->assertArrayHasKey(
            'estudiante',
            $variables
        );

        $this->assertArrayHasKey(
            'actividades',
            $variables
        );

        $this->assertEquals(
            '',
            $variables['buscar']
        );

        $this->assertEquals(
            20230001,
            $variables['estudiante']->carnet
        );
    }
}

/*

vendor/bin/phpunit module/Application/test/Controller/EstudianteControllerTest.php

*/
