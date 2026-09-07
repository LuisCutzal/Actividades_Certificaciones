<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Application\Model\PasswordResetTable;
use Application\Model\Factory\PasswordResetTableFactory;
use Application\Model\Factory\UserTableFactory;
use Application\Model\UserTable;

return [
    //rutas
    'router' => [
        'routes' => [
            //ruta para el index
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/', // URL
                    'defaults' => [
                        'controller' => Controller\IndexController::class, // controlador por defecto
                        'action' => 'index', // acción por defecto
                    ],
                ],
            ],
            // ruta para login: '/login'
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            // ruta para forgot password
            'forgot-password' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/forgot-password',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'forgotPassword',
                    ],
                ],
            ],
            // ruta para resetear password
            'reset-password' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/reset-password',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'resetPassword',
                    ],
                ],
            ],

            // ruta para panel de administrador
            'administrador' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action' => 'dashboard',
                    ],
                ],
                // aquí comienzan las rutas hijas del administrador
                'may_terminate' => true,
                'child_routes' => [
                    // -> /admin/usuarios
                    'agregarUsuarios' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/crearUsuario',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'add-user',
                            ],
                        ],
                    ],
                    'listarUsuarios' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/listarUsuario',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'listar-user',
                            ],
                        ],
                    ],
                    'buscarUsuario' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/usuarios/buscar',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'find-user',
                            ],
                        ],
                    ],

                    'eliminarUsuario' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/usuarios/eliminar/:id',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'eliminar-user', //eliminacion de forma logica
                            ],
                            'constraints' => [
                                'id' => '[0-9]+'
                            ]
                        ],
                    ],
                    'activar' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/usuarios/activar/:id',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'activar-user',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+'
                            ]
                        ],
                    ],
                    'nuevoRol' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/roles/crear',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'crear-rol',
                            ]
                        ],
                    ],
                    'listarRoles' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/roles/listar',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'listar-roles',
                            ]
                        ],
                    ],
                    'modificarRol' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/roles/modificar/:id',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'modificar-rol',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+'
                            ]
                        ],
                    ],
                    'buscarRol' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/roles/buscar',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'find-rol',
                            ],
                        ],
                    ],
                    'dashboardData' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/dashboard-data',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'dashboard-data',
                            ],
                        ],
                    ],
                    'logs' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/logs',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'logs',
                            ],
                        ],
                    ],
                ],
            ],
            // ruta para logout
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            //ruta para editar usuarios-> funciona para todos los usuarios
            'editarUsuario' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/usuario/editar/:id',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action'     => 'editar-user',
                    ],
                    'constraints' => [
                        'id' => '[0-9]+'
                    ]
                ]

            ],
            'dashboard' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/dashboard',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'dashboard',
                    ],
                ],
            ],
            'dashboardData' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/dashboard-data',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action'     => 'dashboardData',
                    ],
                ],
            ],
            'actividades' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/actividades',
                    'defaults' => [
                        'controller' => Controller\ActivityController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'crear' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/crear', //esto sirve para cerar actividades con estudiantes
                            'defaults' => [
                                'action' => 'add-activity',
                            ],
                        ],
                    ],
                    //ahora la actividad donde solo se crea la actividad pero no se asignan estudiantes hasta que no se aprube la actividad, este es el flujo principal del sistema
                    'crear-actividad-simple' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/crearActividad',
                            'defaults' => [
                                'action' => 'crear-actividad-simple',
                            ],
                        ],
                    ],

                    'editar' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/edit/:id',
                            'defaults' => [
                                'action' => 'editar-activity',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],

                    'editar-actividad-simple' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/editsimple/:id',
                            'defaults' => [
                                'action' => 'editar-activity-simple',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],

                    'definir-participacion' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/definir-participacion/:id',
                            'defaults' => [
                                'action' => 'definir-participacion',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],

                    'aprobar' => [ //esto es para aprobar la actividad
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/aprobar/:id',
                            'defaults' => [
                                'action' => 'update-activity',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'rechazar' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/rechazar/:id',
                            'defaults' => [
                                'action' => 'rechazar',
                            ],
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'rechazadas' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/rechazadas',
                            'defaults' => [
                                'action' => 'ListadoRechazo',
                            ]
                        ],
                    ],
                    'ver-rechazo' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/ver-rechazo/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'VerRechazo',
                            ],
                        ],
                    ],
                    'listado' => [ //listado de todas las actividades aprobadas y no aprobadas
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/listadoActividades',
                            'defaults' => [
                                'action' => 'listado',
                            ]
                        ],
                    ],
                    'listaAprobadas' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/listaAprobadas',
                            'defaults' => [
                                'action' => 'listaAprobadas',
                            ]
                        ],
                    ],
                    'detalles' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/detalle/:id',
                            'defaults' => [
                                'action' => 'detalle',
                            ]
                        ],
                    ],
                    'pendientes' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/pendientes',
                            'defaults' => [
                                'action' => 'pendientes',
                            ]
                        ],
                    ],
                ],
            ],
            'attendance' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/actividades/:id/asistencia',
                    'defaults' => [
                        'controller' => Controller\AttendanceController::class,
                        'action'     => 'view',
                    ],
                ]
            ],
            'estudiantes' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/estudiantes',
                    'defaults' => [
                        'controller' => Controller\EstudianteController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'buscar' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/buscar',
                            'defaults' => [
                                'action' => 'buscar',
                            ],
                        ],
                    ],
                    'ver' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/ver/:carnet',
                            'defaults' => [
                                'action' => 'ver',
                            ],
                        ],
                    ],
                ],
            ],
            'reportes' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/reportes',
                    'defaults' => [
                        'controller' => Controller\ReporteController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'actividades' => [ //para ver la lista de actividades
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/actividades',
                            'defaults' => [
                                'action' => 'actividades',
                            ],
                        ],
                    ],
                    'actividad' => [ //ver actividad especifica
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/actividad/:id',
                            'defaults' => [
                                'action' => 'actividad',
                            ],
                        ],
                    ],
                    'actividad-pdf' => [ //esto es para generar pdf con la actividad (sin lista de asistencia)
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/actividad/:id/pdf',
                            'defaults' => [
                                'action' => 'actividadPdf',
                            ],
                        ],
                    ],
                    'actividad-participantes-pdf' => [ //esto es para gener pdf con la lista de asistencia de una actividad
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/actividad/:id/participantes/pdf',
                            'defaults' => [
                                'action' => 'actividadParticipantesPdf',
                            ],
                        ],
                    ],
                    'auditoria-asociacion' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/auditoria/asociacion',
                            'defaults' => [
                                'action' => 'auditoria',
                            ],
                        ],
                    ],
                    'auditoria-estudiantes' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/auditoria/estudiantes',
                            'defaults' => [
                                'action' => 'auditoriaEstudiantes',
                            ],
                        ],
                    ],

                    'reporte-auditoria' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/historial',
                            'defaults' => [
                                'action' => 'historial',
                            ],
                        ],
                    ],
                ],
            ],

        ],
    ],
    'controllers' => [
        'factories' => [
            // indexController no necesita dependencias, se crea invocablemente
            Controller\IndexController::class => InvokableFactory::class,
            // authController necesita dependencias; se registra con una closure/factory
            Controller\AuthController::class => function ($container) {
                return new \Application\Controller\AuthController(
                    $container->get(\Application\Service\AuthService::class), // servicio de autenticación
                    $container->get(PasswordResetTable::class), // tabla para resets de contraseña
                    $container->get(\Application\Service\MailService::class),
                    new \Laminas\Session\Container('user'),
                    $container->get(\Application\Service\AuditService::class)
                );
            },
            // controladores simples sin dependencias
            Controller\AdminController::class => \Application\Controller\Factory\AdminControllerFactory::class,
            Controller\ActivityController::class => \Application\Controller\Factory\ActivityControllerFactory::class,
            Controller\UserController::class => \Application\Controller\Factory\UserControllerFactory::class,
            Controller\AttendanceController::class => \Application\Controller\Factory\AttendanceControllerFactory::class,
            Controller\EstudianteController::class => \Application\Controller\Factory\EstudianteControllerFactory::class,
            Controller\ReporteController::class => \Application\Controller\Factory\ReporteControllerFactory::class,

        ],
    ],
    // plugins de controladores
    'controller_plugins' => [
        'factories' => [
            \Application\Controller\Plugin\AuthPlugin::class =>
            \Application\Controller\Plugin\Factory\AuthPluginFactory::class,
        ],
        'aliases' => [
            // alias más corto para llamar al plugin dentro de controladores: $this->authPlugin()
            'authPlugin' => \Application\Controller\Plugin\AuthPlugin::class,
        ],
    ],

    'view_helpers' => [
        'factories' => [
            \Application\View\Helper\UserIdentity::class =>
            \Application\View\Helper\Factory\UserIdentityFactory::class,
        ],
        'aliases' => [
            'userIdentity' => \Application\View\Helper\UserIdentity::class,
        ],
    ],


    // gestion de vistas
    'view_manager' => [
        'display_not_found_reason' => true, // mostrar razón 404 (útil en dev)
        'display_exceptions'       => true, // mostrar excepciones completas (útil en dev)
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404', // template para 404
        'exception_template'       => 'error/index', // template para excepciones
        'template_map' => [ // mapa explícito de templates a archivos físicos
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',  // carpeta donde buscar vistas
        ],
        'strategies' => [
            'ViewJsonStrategy', // permite devolver JSON fácilmente desde acciones
        ],
    ],
    // gestion de servicios
    'service_manager' => [
        'factories' => [

            'db2' => function ($container) {

                $config = $container->get('config');

                return new \Laminas\Db\Adapter\Adapter(
                    $config['db2']
                );
            },
            // servicio AuthService definido con su factory (clase)
            \Application\Service\AuthService::class => \Application\Service\Factory\AuthServiceFactory::class,
            // tabla para PasswordReset (acceso a BD) — usa su factory
            PasswordResetTable::class => PasswordResetTableFactory::class,
            // tabla de usuarios — usa su factory
            UserTable::class => UserTableFactory::class,

            \Application\Service\UserService::class => \Application\Service\Factory\UserServiceFactory::class,

            \Application\Service\AdminService::class => \Application\Service\Factory\AdminServiceFactory::class,

            \Application\Service\PersonalService::class => \Application\Service\Factory\PersonalServiceFactory::class,

            \Application\Service\SecretariaService::class => \Application\Service\Factory\SecretariaServiceFactory::class,

            \Application\Service\AsociacionService::class => \Application\Service\Factory\AsociacionServiceFactory::class,

            \Application\Service\ActivityService::class => \Application\Service\Factory\ActivityServiceFactory::class,

            \Application\Model\ActivityTable::class => \Application\Model\Factory\ActivityTableFactory::class,

            \Application\Service\AttendanceService::class => \Application\Service\Factory\AttendanceServiceFactory::class,


            \Application\Service\EstudianteService::class => \Application\Service\Factory\EstudianteServiceFactory::class,
            \Application\Service\AuditService::class => \Application\Service\Factory\AuditServiceFactory::class,



            \Application\Model\AttendanceListTable::class => \Application\Model\Factory\AttendanceListTableFactory::class,
            \Application\Model\AttendanceListStudentTable::class => \Application\Model\Factory\AttendanceListStudentTableFactory::class,
            \Application\Model\StudentAttendanceTable::class => \Application\Model\Factory\StudentAttendanceTableFactory::class,
            \Application\Model\StudentTable::class => \Application\Model\Factory\StudentTableFactory::class,

            \Application\Model\RolTable::class => \Application\Model\Factory\RolTableFactory::class,
            \Application\Service\ReporteService::class => \Application\Service\Factory\ReporteServiceFactory::class,

            \Application\Model\ReportePdfTable::class => \Application\Model\Factory\ReportePdfTableFactory::class,

            \Application\Model\CarreraTable::class => \Application\Model\Factory\CarreraTableFactory::class,
            \Application\Model\CarreraEstudianteTable::class => \Application\Model\Factory\CarreraEstudianteTableFactory::class,

            \Application\Model\ExtensionTable::class => \Application\Model\Factory\ExtensionTableFactory::class,
            \Application\Model\InscripcionTable::class => \Application\Model\Factory\InscripcionTableFactory::class,
            \Application\Model\RolesPermisosTable::class => \Application\Model\Factory\RolesPermisosTableFactory::class,
            \Application\Model\PermisosTable::class => \Application\Model\Factory\PermisosTableFactory::class,
            \Application\Model\RolCarreraTable::class => \Application\Model\Factory\RolCarreraTableFactory::class,
            \Application\Model\TipoTable::class => \Application\Model\Factory\TipoTableFactory::class,

            \Application\Model\PlantillasPDFTable::class => \Application\Model\Factory\PlantillasPDFTableFactory::class,

            \Application\Model\AuditLogTable::class => \Application\Model\Factory\AuditLogTableFactory::class,

            \Application\Service\MailService::class => function ($container) {
                $config = $container->get('config')['mail'];
                return new \Application\Service\MailService($config);
            },


        ],
    ],
];
