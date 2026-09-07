<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Service\AdminService;
use Application\Model\RolTable;
use Application\Service\ActivityService;
use Application\Model\ExtensionTable;
use Application\Service\AuditService;

use Laminas\View\Model\JsonModel;


use Application\Utils\PaginatorHelper;

// Controlador del área de administración.
class AdminController extends BaseController
{
    private AdminService $adminService;
    //private RolTable $rolTable;
    private ActivityService $activityService;
    //private ExtensionTable $extensionTable;
    private AuditService $auditService;

    public function __construct(
        AdminService $adminService,
        RolTable $rolTable,
        ActivityService $activityService,
        ExtensionTable $extensionTable,
        AuditService $auditService
    ) {
        parent::__construct($extensionTable, $rolTable);

        $this->adminService = $adminService;
        $this->activityService = $activityService;
        $this->auditService = $auditService;
    }
    //Página principal del panel de administrador (/admin)

    public function dashboardAction()
    {
        $user = $this->checkAuth([1]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        return [];
    }

    public function dashboardDataAction()
    {
        $user = $this->checkAuth([1]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $filters = [

            'year' =>
            $this->params()->fromQuery('year'),

            'monthStart' =>
            $this->params()->fromQuery('monthStart'),

            'monthEnd' =>
            $this->params()->fromQuery('monthEnd'),

            'dateStart' =>
            $this->params()->fromQuery('dateStart'),

            'dateEnd' =>
            $this->params()->fromQuery('dateEnd'),
        ];

        $conteoEstados =
            $this->activityService
            ->getConteoActividadesPorEstado($user);

        $totalActividades =
            $conteoEstados['pendientes']
            + $conteoEstados['aprobadas']
            + $conteoEstados['rechazadas'];

        return new JsonModel([

            'kpis' => [
                'total'      => $totalActividades,
                'aprobadas'  => $conteoEstados['aprobadas'],
                'pendientes' => $conteoEstados['pendientes'],
                'rechazadas' => $conteoEstados['rechazadas'],
            ],

            'actividadesPorMes' =>
            $this->activityService
                ->getActividadesPorMes($user, $filters),

            'aprobadasVsPendientes' =>
            $this->activityService
                ->getAprobadasVsPendientes($user, $filters),

            'topActividades' =>
            $this->activityService
                ->getTopActividades($user, $filters),

            'topUsuarios' =>
            $this->activityService
                ->getTopUsuarios($user, $filters),

            'actividadesPorCarrera' =>
            $this->activityService
                ->getActividadesPorCarrera($user, $filters),
        ]);
    }

    public function addUserAction()
    {
        $user = $this->checkAuth([1]); // verificar que solo la funcion es para administradores

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        // Obtener roles desde la base de datos
        $roles = $this->rolTable->getRol() ?? [];

        // Convertir a array simple id => nombre
        $mapaRoles = [];

        foreach ($roles as $rol) {
            $mapaRoles[$rol->id] = $rol->nombre;
        }

        $view = new \Laminas\View\Model\ViewModel([
            'roles' => $mapaRoles,
        ]);

        $view->setTemplate('application/usuario/add-user');

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            try {

                $this->adminService->createUserAsAdmin($data);

                // LOG ÉXITO
                $this->auditService->log(
                    (int)$user['id'],
                    (string)($user['cui'] ?? $user['nombre']),
                    'USER_CREATED',
                    'USER',
                    (int)$user['id'],
                    'Se creó un nuevo usuario',
                    [
                        'cui' => $data['cui'] ?? null,
                        'rol' => $data['rol_id'] ?? null
                    ]
                );

                $view->setVariable(
                    'successMessage',
                    'Usuario creado correctamente'
                );
            } catch (\Exception $e) {

                // LOG ERROR
                $this->auditService->log(
                    (int)$user['id'],
                    (string)($user['cui'] ?? null),
                    'USER_CREATE_FAILED',
                    'USER',
                    (int)$user['id'],
                    'Error al crear usuario: ' . $e->getMessage()
                );

                $view->setVariable(
                    'errorMessage',
                    $e->getMessage()
                );
            }
        }

        return $view;
    }

    public function listarUserAction()
    {
        $user = $this->checkAuth([1]); // Solo administradores
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }
        // Obtener usuarios
        $usuarios = $this->adminService->obtenerUsuarios();

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($usuarios, $page, 10);

        // Obtener roles desde la base de datos
        $rolesResult = $this->rolTable->getRol() ?? []; // Devuelve ResultSet o array
        $mapaRoles = [];

        foreach ($rolesResult as $rol) {
            if (is_array($rol)) {
                $mapaRoles[(int)$rol['id']] = $rol['nombre'];
            } elseif (is_object($rol)) {
                $mapaRoles[(int)$rol->id] = $rol->nombre;
            }
        }

        $view = new \Laminas\View\Model\ViewModel([
            'usuarios' => $paginator,
            'roles' => $mapaRoles,
        ]);

        $view->setTemplate('application/usuario/index-user');

        return $view;
    }


    public function findUserAction()
    {
        //$query = $this->params()->fromQuery('q');
        $query = trim($this->params()->fromQuery('q', ''));
        if (!$query) {
            return $this->redirect()->toUrl('/listarUsuario');
        }
        $usuarios = $this->adminService->buscarUsuarios($query);
        //error_log("Resultado de búsqueda: " . print_r($usuarios, true));
        $view = new ViewModel([
            'usuarios' => $usuarios,
            'query'    => $query
        ]);
        $view->setTemplate('application/usuario/search-user');
        return $view;
    }

    public function editarUserAction() //este nombre debe ser igual al action que aparece en module.config.php
    {
        $user = $this->checkAuth([1]); // Solo administradores
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        return $this->handleUserEdit(
            fn($id) => $this->adminService->obtenerUsuarioPorId($id),
            fn($data) => $this->adminService->editUserAsAdmin($data),
            '/admin/listarUsuario',
            '/'
        );
    }

    public function eliminarUserAction()
    {
        $user = $this->checkAuth([1]); // Solo administradores
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id');
        if (!$id) {
            return $this->redirect()->toUrl('/admin/listarUsuario');
        }

        $userToDelete = $this->adminService->getUserById($id);
        if (!$userToDelete) {
            return $this->redirect()->toUrl('/admin/listarUsuario');
        }

        $this->adminService->eliminarUsuarioAsAdmin($id);

        //comienza el log
        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? null),
            'USER_DELETED',
            'USER',
            (int)$id,
            'Se eliminó un usuario',
            [
                'cui' => $userToDelete?->cui ?? null,
            ]
        );

        return $this->redirect()->toUrl('/admin/listarUsuario');
    }

    public function activarUserAction()
    {
        $user = $this->checkAuth([1]); // Solo administradores
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id');
        if (!$id) {
            return $this->redirect()->toUrl('/admin/listarUsuario');
        }

        $userToDelete = $this->adminService->getUserById($id);
        if (!$userToDelete) {
            return $this->redirect()->toUrl('/admin/listarUsuario');
        }

        $this->adminService->activarUsuarioAsAdmin($id);

        //log

        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? null),
            'USER_ACTIVATED',
            'USER',
            (int)$id,
            'Se activó un usuario',
            [
                'cui' => $userToDelete?->cui ?? null,
            ]
        );
        return $this->redirect()->toUrl('/admin/listarUsuario');
    }


    //para crear roles

    public function crearRolAction()
    {
        // Solo administradores (rol 1)
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        // Traer datos para el formulario
        $permisos = $this->adminService->getPermisos();
        $carreras = $this->adminService->getCarreras();
        $tipos = $this->adminService->getTipos(); // Tipos de rol
        $extensiones = $this->extensionTable->fetchAll();

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            try {
                // Validar nombre
                $nombre = trim($data['nombre'] ?? '');
                if (!$nombre) {
                    throw new \Exception('Debes ingresar un nombre para el rol.');
                }

                // Validar tipo
                $tipoId = $data['tipo_id'] ?? null;
                if (!$tipoId) {
                    throw new \Exception('Debes seleccionar un tipo de rol.');
                }

                // Permisos
                $permisosSeleccionados = $data['permisos'] ?? [];
                if (!is_array($permisosSeleccionados)) {
                    $permisosSeleccionados = [$permisosSeleccionados];
                }

                //validar extension
                $extension = (int)($data['extension'] ?? 0);
                // var_dump($extension); // Agrega esta línea para depuración
                // exit();
                if (!in_array($extension, [1, 2])) { //aca verificamos la extension
                    throw new \Exception('Debes seleccionar una extensión válida.');
                }

                // Carreras (solo si hay permiso "crear actividad")
                $carrerasSeleccionadas = $data['carreras'] ?? [];
                if (!is_array($carrerasSeleccionadas)) {
                    $carrerasSeleccionadas = [$carrerasSeleccionadas];
                }

                // Llamada al servicio para crear el rol
                $this->adminService->crearRol(
                    $nombre,
                    $permisosSeleccionados,
                    (int)$tipoId,          // tipo de rol
                    $extension,
                    $carrerasSeleccionadas,  // carreras permitidas
                );

                $this->auditService->log(
                    (int)$user['id'],
                    (string)($user['cui'] ?? null),
                    'ROLE_CREATED',
                    'ROLE',
                    (int)$user['id'],
                    'Se creó un nuevo rol',
                    [

                        'nombre' => $nombre,
                        'tipo_id' => (int)$tipoId,
                        'extension' => $extension

                    ]

                );

                // Vista con mensaje de éxito
                $view = new ViewModel([
                    'permisos' => $permisos,
                    'carreras' => $carreras,
                    'tipos' => $tipos,
                    'extensiones' => $extensiones,
                    'successMessage' => 'Rol creado correctamente.'
                ]);
            } catch (\Exception $e) {
                // Vista con mensaje de error
                $view = new ViewModel([
                    'permisos' => $permisos,
                    'carreras' => $carreras,
                    'tipos' => $tipos,
                    'extensiones' => $extensiones,
                    'errorMessage' => $e->getMessage()
                ]);
            }

            $view->setTemplate('application/rol/crear-rol');
            return $view;
        }

        // GET: Mostrar formulario
        $view = new ViewModel([
            'permisos' => $permisos,
            'carreras' => $carreras,
            'tipos' => $tipos,
            'extensiones' => $extensiones
        ]);
        $view->setTemplate('application/rol/crear-rol');

        return $view;
    }


    public function listarRolesAction()
    {
        $user = $this->checkAuth([1]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        // obtener roles desde el service
        $roles = $this->adminService->obtenerRoles();
        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($roles, $page, 7);

        $view = new ViewModel([
            'roles' => $paginator
        ]);

        $view->setTemplate('application/rol/index-rol');

        return $view;
    }


    public function findRolAction()
    {

        $user = $this->checkAuth([1]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $query = trim($this->params()->fromQuery('q', ''));

        if (!$query) {
            return $this->redirect()->toUrl('/listarRoles');
        }
        $roles = $this->adminService->buscarRoles($query);
        $view = new ViewModel([
            'roles' => $roles,
            'query'    => $query
        ]);
        $view->setTemplate('application/rol/search-rol');
        return $view;
    }

    public function modificarRolAction()
    {
        $user = $this->checkAuth([1]); // solo admin

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id');

        // obtener rol
        $rol = $this->adminService->obtenerRolPorId($id);

        if (!$rol) {
            return $this->redirect()->toUrl('/roles/listar');
        }

        // obtener permisos
        $permisos = $this->adminService->getPermisos();

        $permisosRol =
            $this->adminService
            ->getPermisosPorRol($id);

        $carreras =
            $this->adminService
            ->getCarreras();

        $carrerasRol =
            $this->adminService
            ->getCarrerasPorRol($id); //obtenemos las carreras que tiene el rol nada mas

        $extensiones = $this->extensionTable->fetchAll();


        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            try {

                // Validar nombre
                $nombre = trim($data['nombre'] ?? '');

                if (!$nombre) {
                    throw new \Exception(
                        'Debes ingresar un nombre para el rol.'
                    );
                }

                // Validar extensión
                $extension = (int)($data['extension'] ?? 0);

                if (!in_array($extension, [1,2])) {

                    throw new \Exception(
                        'Debes seleccionar una extensión válida.'
                    );
                }

                // Permisos
                $permisosSeleccionados =
                    $data['permisos'] ?? [];

                if (!is_array($permisosSeleccionados)) {

                    $permisosSeleccionados =
                        [$permisosSeleccionados];
                }

                // Carreras
                $carrerasSeleccionadas =
                    $data['carreras'] ?? [];

                if (!is_array($carrerasSeleccionadas)) {

                    $carrerasSeleccionadas =
                        [$carrerasSeleccionadas];
                }

                $this->adminService->modificarRol(
                    $id,
                    $nombre,
                    $extension,
                    $permisosSeleccionados,
                    $carrerasSeleccionadas
                );

                // LOG ÉXITO
                $this->auditService->log(
                    (int)$user['id'],
                    (string)($user['cui'] ?? null),
                    'ROLE_UPDATED',
                    'ROLE',
                    $id,
                    'Se modificó un rol',
                    [
                        'nombre' => $nombre,
                        'extension' => $extension
                    ]
                );

                return $this->redirect()
                    ->toUrl('/admin/roles/listar');
            } catch (\Exception $e) {

                // LOG ERROR
                $this->auditService->log(
                    (int)$user['id'],
                    (string)($user['cui'] ?? null),
                    'ROLE_UPDATE_FAILED',
                    'ROLE',
                    $id,
                    $e->getMessage(),
                    [
                        'nombre' => $data['nombre'] ?? null,
                        'extension' => $data['extension'] ?? null
                    ]
                );

                $view = new ViewModel([
                    'rol' => $rol,
                    'permisos' => $permisos,
                    'permisosRol' => $permisosRol,
                    'carreras' => $carreras,
                    'carrerasRol' => $carrerasRol,
                    'extensiones' => $extensiones,
                    'errorMessage' => $e->getMessage()
                ]);

                $view->setTemplate(
                    'application/rol/modificar-rol'
                );

                return $view;
            }
        }

        $view = new ViewModel([
            'rol' => $rol,
            'permisos' => $permisos,
            'permisosRol' => $permisosRol,
            'carreras' => $carreras,
            'carrerasRol' => $carrerasRol,
            'extensiones' => $extensiones,
        ]);

        $view->setTemplate('application/rol/modificar-rol');
        return $view;
    }

    public function logsAction()
    {
        $user = $this->checkAuth([1]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $entityType = trim($this->params()->fromQuery('entity_type', '')) ?: null;
        $entityId   = $this->params()->fromQuery('entity_id', '');
        $action     = trim($this->params()->fromQuery('action', '')) ?: null;

        $entityId = $entityId !== '' ? (int)$entityId : null;

        $page = (int) $this->params()->fromQuery('page', 1);

        // 👇 AQUÍ está el cambio importante
        $logs = $this->auditService->getLogs(
            $entityType,
            $entityId,
            $action
        );

        $paginator = PaginatorHelper::paginate($logs, $page, 20);

        $view = new ViewModel([
            'logs'       => $paginator,
            'entityType' => $entityType,
            'entityId'   => $entityId,
            'action'     => $action,
            'query'      => [
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'action'      => $action,
            ]
        ]);

        $view->setTemplate('application/audit/logs');

        return $view;
    }
}
