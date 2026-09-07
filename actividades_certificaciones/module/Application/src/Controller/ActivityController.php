<?php

namespace Application\Controller;

use Application\Service\ActivityService;
use Application\Service\AttendanceService;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Model\RolTable;
use Application\Model\InscripcionTable;
use Application\Model\CarreraTable;
use Application\Model\ExtensionTable;
use Application\Service\AuditService;
use Laminas\Http\Response;

use Application\Utils\PaginatorHelper;

class ActivityController extends BaseController
{
    private ActivityService $activityService;
    private AttendanceService $attendanceService;
    private Adapter $adapter;
    //private RolTable $rolTable;
    private CarreraTable $carreraTable;
    //private CarreraEstudianteTable $carreraEstudianteTable;
    private AuditService $auditService;
    private InscripcionTable $inscripcionTable;

    public function __construct(
        ActivityService $activityService,
        AttendanceService $attendanceService,
        Adapter $adapter,
        ExtensionTable $extensionTable,
        RolTable $rolTable,
        CarreraTable $carreraTable,
        AuditService $auditService,
        InscripcionTable $inscripcionTable,
        //CarreraEstudianteTable $carreraEstudianteTable,
    ) {
        parent::__construct($extensionTable, $rolTable);
        $this->activityService = $activityService;
        $this->attendanceService = $attendanceService;
        $this->adapter = $adapter;
        $this->rolTable = $rolTable;
        $this->carreraTable = $carreraTable;
        $this->auditService = $auditService;
        $this->inscripcionTable = $inscripcionTable;
        //$this->carreraEstudianteTable = $carreraEstudianteTable;
    }

    public function indexAction()
    {
        $user = $this->checkAuth([2, 3, 4]);
        if ($user instanceof Response) {
            return $user;
        }

        $buscar = $this->params()->fromQuery('buscar', '');
        $buscar = trim($buscar);

        $page = (int) $this->params()->fromQuery('page', 1);

        $canCreate = true;

        $hayPendientes = count(
            $this->activityService->obtenerActividadesPendientes(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            )
        ) > 0;

        if ($buscar) {
            $actividades = $this->activityService->buscarActividad(
                $buscar,
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            );
        } elseif (in_array($user['tipo_id'], [3])) {
            $actividades = $this->activityService->obtenerActividadesPendientes(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            );
        } else {
            $actividades = $this->activityService->listarActividades(
                $user['tipo_id'],
                $user['carreras'],
                $user['extension'],
                $user['rol_id']
            );
        }

        $paginator = PaginatorHelper::paginate($actividades, $page, 10);

        $view = new ViewModel([
            'actividades'  => $paginator,
            'canCreate'    => $canCreate,
            'tipo_id'      => $user['tipo_id'],
            'esPendientes' => in_array($user['tipo_id'], [3]) && !$buscar,
            'hayPendientes' => $hayPendientes,
            'buscar'       => $buscar,
            'query' => [
                'buscar' => $buscar
            ]
        ]);

        $view->setTemplate('application/actividad/index');
        return $view;
    }
    
    private function getFormularioActividadData(array $user): array
    {
        // Roles
        $roles = $this->obtenerMapaRoles();

        // Carreras
        $carreras = $this->obtenerMapaCarreras();
        $carrerasUsuario = $user['carreras'] ?? [];

        foreach ($carreras as $id => $nombre) {

            if (!in_array($id, $carrerasUsuario)) {
                unset($carreras[$id]);
            }
        }

        // Carrera asignada
        $carreraAsignadaId = null;
        $carreraAsignadaNombre = null;

        if (count($carrerasUsuario) === 1) {
            $carreraAsignadaId = $carrerasUsuario[0];
            $carreraAsignadaNombre = $carreras[$carreraAsignadaId] ?? '';
        }

        return [
            'rol_id' => $user['rol_id'],
            'tipo_id' => $user['tipo_id'] ?? null,
            'roles' => $roles,
            'carreras' => $carreras,
            'carreraAsignadaId' => $carreraAsignadaId,
            'carreraAsignadaNombre' => $carreraAsignadaNombre,
        ];
    }

    private function agregarExtensionUsuario(array &$data, array $user): void
    {
        $data['extension'] = $user['extension'] ?? null;

        if ($data['extension'] === null) {
            throw new \Exception('No se pudo determinar la extensión del usuario.');
        }
    }

    private function renderFormularioActividad(
        array $user,
        string $template,
        array $extraData = []
    ) {
        $data = array_merge(
            $this->getFormularioActividadData($user),
            $extraData
        );

        $view = new ViewModel($data);
        $view->setTemplate($template);

        return $view;
    }

    public function addActivityAction()
    {
        $user = $this->checkAuth([2, 4], 'crear_actividad');

        if ($user instanceof Response) {
            return $user;
        }

        if (!$this->getRequest()->isPost()) {

            return $this->renderFormularioActividad(
                $user,
                'application/actividad/add-activity'
            );
        }

        $connection = $this->adapter->getDriver()->getConnection();
        try {

            $connection->beginTransaction();

            $data = $this->params()->fromPost();
            $files = $this->params()->fromFiles();

            $this->agregarExtensionUsuario($data, $user);

            $activityId = $this->activityService->createActivity($data);

            if (!$activityId) {
                throw new \Exception('No se pudo crear la actividad.');
            }

            $this->procesarParticipacion(
                $user['extension'],
                $activityId,
                $data,
                $files,
                true
            );

            $connection->commit();

            //log para crear actividad
            $this->auditService->log(
                (int)$user['id'],
                (string)$user['cui'],
                'ACTIVITY_CREATED',
                'ACTIVITY',
                (int)$activityId,
                'Se creó una nueva actividad',
                [
                    'nombre_actividad' => $data['nombre'] ?? null,
                    'tipo_usuario' => $user['tipo_id'] ?? null,
                    'fecha' => $data['fecha'] ?? null,
                ]
            );

            return $this->redirect()->toUrl('/actividades');
        } catch (\Exception $e) {

            $connection->rollback();

            $this->flashMessenger()->addErrorMessage(
                $e->getMessage()
            );

            return $this->redirect()->toUrl('/actividades');
        }
    }

    private function procesarParticipacion(
        int $userExtension,
        int $activityId,
        array $data,
        array $files,
        bool $esHistorico = false
    ): void {

        $tipo = $data['tipo_participacion'] ?? null;
        $carrera = (int)$data['carrera'];

        if ($tipo === 'lista') {

            $archivo = $files['lista_asistencia'] ?? null;

            if (!$archivo || empty($archivo['tmp_name'])) {
                throw new \Exception("Debe subir el archivo de lista.");
            }

            $resultado = $this->attendanceService->crearLista(
                $activityId,
                $archivo
            );

            $estudiantesOtraExtension = [];
            $estudiantesSinInscripcion = [];

            foreach ($resultado['carnets'] as $item) {

                $carnet = (int)$item['carnet'];

                $inscripcion = $this->inscripcionTable
                    ->obtenerCarreraActual($carnet)
                    ->current();

                if (!$inscripcion) {
                    $estudiantesSinInscripcion[] = $carnet;
                    continue;
                }

                if ((int)$inscripcion->extension !== $userExtension) {
                    $estudiantesOtraExtension[] = $carnet;
                }
            }

            $errores = [];

            if (!empty($estudiantesOtraExtension)) {
                $errores[] =
                    "Los siguientes estudiantes pertenecen a otra extensión: "
                    . implode(', ', $estudiantesOtraExtension);
            }

            if (!empty($estudiantesSinInscripcion)) {
                $errores[] =
                    "Los siguientes estudiantes no tienen inscripción registrada: "
                    . implode(', ', $estudiantesSinInscripcion);
            }

            if (!empty($errores)) {
                throw new \Exception(
                    implode(PHP_EOL, $errores)
                );
            }

            $listaId = $resultado['listaId'];

            $this->attendanceService->registrarEstudiantesMasivo(
                $activityId,
                $resultado['carnets'],
                $carrera,
                $data['extension'],
                $listaId,
                $esHistorico
            );

            return;
        }

        if ($tipo === 'individual') {

            $carne = $data['estudiante_individual'] ?? null;

            if (!$carne) {
                throw new \Exception(
                    "Debe ingresar el carnet del estudiante."
                );
            }

            $inscripcion = $this->inscripcionTable
                ->obtenerCarreraActual((int)$carne)
                ->current();

            if (!$inscripcion) {
                throw new \Exception(
                    "El estudiante no tiene inscripción registrada."
                );
            }

            $data['extension'] = (int)$inscripcion->extension;

            if ((int)$data['extension'] !== $userExtension) {
                throw new \Exception(
                    "No puede registrar estudiantes de otra extensión."
                );
            }

            $listaId = $this->attendanceService
                ->crearListaVacia($activityId);

            $this->attendanceService->registrarEstudianteEnActividad(
                $activityId,
                (int)$carne,
                $carrera,
                $data['extension'],
                $listaId,
                $esHistorico
            );

            return;
        }

        throw new \Exception('Tipo de participación inválido.');
    }

    public function crearActividadSimpleAction()
    {
        $user = $this->checkAuth([2, 4], 'crear_actividad');

        if ($user instanceof Response) {
            return $user;
        }

        if (!$this->getRequest()->isPost()) {

            return $this->renderFormularioActividad(
                $user,
                'application/actividad/crear-actividad-simple',
                [
                    'modo' => 'simple',
                ]
            );
        }

        $connection = $this->adapter->getDriver()->getConnection();

        try {

            $connection->beginTransaction();

            $data = $this->params()->fromPost();

            $data['tipo_participacion'] = null;
            $data['estado'] = 0;

            $this->agregarExtensionUsuario($data, $user);

            $activityId = $this->activityService
                ->createActivitySimple($data);

            if (!$activityId) {
                throw new \Exception(
                    'No se pudo crear la actividad.'
                );
            }

            $connection->commit();

            //log crear actividad 

            $this->auditService->log(
                (int)$user['id'],
                (string)($user['cui'] ?? $user['nombre']),
                'ACTIVITY_CREATED',
                'ACTIVITY',
                (int)$activityId,
                'Se creó una nueva actividad sin participantes',
                [
                    'nombre_actividad' => $data['nombre'] ?? null,
                    'tipo_usuario' => $user['tipo_id'] ?? null,
                    'fecha' => $data['fecha'] ?? null,
                ]
            );

            return $this->redirect()->toUrl('/actividades');
        } catch (\Exception $e) {

            $connection->rollback();

            $this->flashMessenger()->addErrorMessage(
                $e->getMessage()
            );

            return $this->redirect()->toUrl('/actividades');
        }
    }

    private function obtenerActividadEditable()
    {
        $idActividad = (int) $this->params()->fromRoute('id');

        if (!$idActividad) {
            return null;
        }

        $actividad = $this->activityService
            ->obtenerActividadPorId($idActividad);

        if (!$actividad) {
            throw new \Exception('La actividad no existe.');
        }

        if ((int)$actividad->estado === 1) {
            throw new \Exception(
                'No se puede modificar una actividad ya aprobada.'
            );
        }

        return $actividad;
    }

    private function obtenerEstudianteActividad(
        int $actividadId,
        ?string $tipoParticipacion
    ) {
        if ($tipoParticipacion !== 'individual') {
            return null;
        }

        return $this->attendanceService
            ->obtenerEstudiantePorActividad($actividadId);
    }

    private function renderEditarActividad(
        array $user,
        object $actividad,
        array $extraData = []
    ) {

        $data = array_merge(
            $this->getFormularioActividadData($user),
            [
                'actividad' => $actividad,
                'estudiante' => $this->obtenerEstudianteActividad(
                    $actividad->id,
                    $actividad->tipo_participacion
                ),
            ],
            $extraData
        );

        $view = new ViewModel($data);

        $view->setTemplate('application/actividad/edit');

        return $view;
    }

    public function editarActivityAction()
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $actividad = $this->obtenerActividadEditable();

        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }

        if (!$this->getRequest()->isPost()) {

            return $this->renderEditarActividad(
                $user,
                $actividad
            );
        }

        $connection = $this->adapter
            ->getDriver()
            ->getConnection();

        try {

            $connection->beginTransaction();

            $data = $this->params()->fromPost();
            $files = $this->params()->fromFiles();

            $data['id'] = $actividad->id;

            $this->agregarExtensionUsuario($data, $user);

            // Mantener tipo anterior
            if (empty($data['tipo_participacion'])) {
                $data['tipo_participacion']
                    = $actividad->tipo_participacion;
            }

            // Crédito float
            if (isset($data['credito'])) {
                $data['credito'] = (float)$data['credito'];
            }

            // Validar carrera
            if (in_array((int)$user['tipo_id'], [2, 3])) {

                if (empty($data['carrera'])) {
                    throw new \Exception(
                        'Debe seleccionar una carrera.'
                    );
                }

                $data['carrera'] = trim($data['carrera']);
            }

            // Actualizar actividad
            $this->activityService->editActivity($data);

            // Eliminar lista anterior
            $listaVieja = $this->attendanceService
                ->obtenerListaPorActividad($actividad->id);

            if ($listaVieja) {
                $this->attendanceService
                    ->eliminarLista($listaVieja->id);
            }

            // Registrar participación
            $this->procesarParticipacion(
                $user['extension'],
                $actividad->id,
                $data,
                $files,
                true
            );

            $connection->commit();

            //para el log
            $this->auditService->log(
                (int)$user['id'],
                (string)($user['cui'] ?? $user['nombre']),
                'ACTIVITY_UPDATED',
                'ACTIVITY',
                (int)$actividad->id,
                'Se actualizó una actividad con participantes',
                [
                    'nombre' => $data['nombre'] ?? null,
                    'credito' => $data['credito'] ?? null,
                    'tipo_participacion' => $data['tipo_participacion'] ?? null
                ]
            );

            $actividadActualizada = $this->activityService
                ->obtenerActividadPorId($actividad->id);

            return $this->renderEditarActividad(
                $user,
                $actividadActualizada,
                [
                    'success' => 'Cambios guardados correctamente.'
                ]
            );
        } catch (\Exception $e) {

            $connection->rollback();

            //log error al actualizar actividad
            $this->auditService->log(
                (int)$user['id'],
                (string)($user['cui'] ?? $user['nombre']),
                'ACTIVITY_UPDATE_FAILED',
                'ACTIVITY',
                (int)$actividad->id,
                $e->getMessage()
            );

            return $this->renderEditarActividad(
                $user,
                $actividad,
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    public function editarActivitySimpleAction()
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $actividad = $this->obtenerActividadEditable();

        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }

        if ($actividad->tipo_participacion !== null) {
            throw new \Exception(
                'Esta actividad ya tiene participantes.'
            );
        }

        if (!$this->getRequest()->isPost()) {

            return $this->renderEditarActividad(
                $user,
                $actividad
            );
        }

        $data = $this->params()->fromPost();

        $data['id'] = $actividad->id;

        $this->activityService->editActivitySimple($data);

        //log para editar
        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? $user['nombre']),
            'ACTIVITY_UPDATED_SIMPLE',
            'ACTIVITY',
            (int)$actividad->id,
            'Se editó una actividad sin participantes'
        );

        return $this->redirect()->toUrl('/actividades');
    }


    //aca comienza el boton para aprobar la actividad
    public function updateActivityAction()
    {
        $user = $this->checkAuth([3]);

        if ($user instanceof Response) {
            return $user;
        }

        $actividad = $this->obtenerActividadPorIdDesdeRuta();

        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }

        $usuarioAprobadorId = (int) ($user['id'] ?? 0);

        $this->activityService->ActualizarActividad(
            $actividad->id,
            $usuarioAprobadorId
        );

        if ($actividad->tipo_participacion !== null) {
            $this->aprobarListaActividad($actividad->id);
        }

        //log 
        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? $user['nombre']),
            'ACTIVITY_APPROVED',
            'ACTIVITY',
            (int)$actividad->id,
            'Actividad aprobada'
        );

        //return $this->redirigirReferer('/actividades');
        return $this->redirect()->toUrl('/actividades');
    }

    private function aprobarListaActividad(int $actividadId): void
    {
        $lista = $this->attendanceService
            ->obtenerListaPorActividad($actividadId);

        if (!$lista || !is_object($lista)) {
            return;
        }

        $this->attendanceService
            ->aprobarLista($lista->id);
    }

    //aca comienza el boton para rechazar la actividad
    public function RechazarAction()
    {
        $user = $this->checkAuth([3]); //tipo 3 .> secretaria es el unico que puede rechazar actividades
        if ($user instanceof Response) {
            return $user;
        }
        $usuarioAprobadorId = (int) $user['id'];
        $id = (int) $this->params()->fromRoute('id');

        if (!$id) {
            return $this->redirect()->toUrl('/actividades');
        }

        $actividad = $this->activityService->obtenerActividadPorId($id);

        if (!$actividad) {
            throw new \Exception('La actividad no existe.');
        }
        if ($actividad->estado == 2) {
            return $this->redirect()->toUrl('/actividades/rechazadas');
        }

        $request = $this->getRequest();

        // Obtener roles desde la tabla rol
        $roles = $this->obtenerMapaRoles();

        // POST → guardar rechazo
        if ($request->isPost()) {
            $motivo = trim($request->getPost('motivo_rechazo'));

            if ($motivo === '') {
                $view = new ViewModel([
                    'actividad' => $actividad,
                    'error'     => 'Debe ingresar un motivo de rechazo.',
                    'roles'     => $roles,
                ]);

                $view->setTemplate('application/actividad/rechazo');
                return $view;
            }

            $this->activityService->RechazarActividad($id, $motivo, $usuarioAprobadorId);

            $this->auditService->log(
                (int)$user['id'],
                (string)($user['cui'] ?? $user['nombre']),
                'ACTIVITY_REJECTED',
                'ACTIVITY',
                (int)$id,
                'Actividad rechazada',
                [
                    'motivo' => $motivo
                ]
            );

            return $this->redirect()->toUrl('/actividades/rechazadas');
        }

        // GET → formulario
        $view =  new ViewModel([
            'actividad' => $actividad,
            'roles'     => $roles,
        ]);

        $view->setTemplate('application/actividad/rechazo');
        return $view;
    }

    public function ListadoRechazoAction()
    {
        $user = $this->checkAuth([2, 3, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $buscar = trim($this->params()->fromQuery('buscar', ''));
        //aca tengo que poner algo de codgio carrera asi como estaba en add activity y para extension tambien
        // echo "<pre>";
        // print_r($user);
        // echo "</pre>";
        // exit;
        $actividades = $this->activityService
            ->obtenerActividadesRechazadas(
                $user['tipo_id'],
                $user['carreras'],
                $user['extension'],
                $buscar
            ) ?? [];

        $roles = $this->obtenerMapaRoles();

        $view = new ViewModel([
            'actividades' => $actividades,
            'rol_id'      => $user['rol_id'],
            'tipo_id'     => $user['tipo_id'],
            'roles'       => $roles,
            'esPendientes' => false,
            'esAprobado'  => false,
            'esRechazo'   => true,
            'buscar'      => $buscar,
        ]);

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginated = PaginatorHelper::paginate($actividades, $page, 15);

        $view->setVariable(
            'actividades',
            $paginated ?? []
        );

        $view->setTemplate('application/actividad/rechazadas');
        return $view;
    }

    public function VerRechazoAction()
    {
        $user = $this->checkAuth([1, 2, 3, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id');
        if (!$id) {
            return $this->redirect()->toUrl('/actividades/rechazadas');
        }

        $actividad = $this->activityService->obtenerActividadPorId($id);

        if (!$actividad || $actividad->estado != 2) {
            return $this->redirect()->toUrl('/actividades/rechazadas');
        }

        // Obtener roles desde la tabla rol
        $roles = $this->obtenerMapaRoles();

        $view = new ViewModel([
            'actividad' => $actividad,
            'roles'     => $roles, // <-- enviamos los roles
        ]);
        $view->setTemplate('application/actividad/ver-rechazo');
        return $view;
    }

    public function ListadoAction() //para obtener todas las actividades 
    {
        $user = $this->checkAuth([3]);
        if ($user instanceof Response) {
            return $user;
        }

        $actividades = $this->activityService
            ->obtenerTodasLasActividades(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            );

        $hayPendientes = count(
            $this->activityService->obtenerActividadesPendientes(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            )
        ) > 0;

        $view = new ViewModel([
            'actividades'   => $actividades,
            'rol_id'           => $user['rol_id'],
            'tipo_id'       => $user['tipo_id'],
            'esPendientes'  => false,
            'esAprobado'    => false,
            'hayPendientes' => $hayPendientes,
        ]);

        $page = (int) $this->params()->fromQuery('page', 1);

        $view->setVariable('actividades', PaginatorHelper::paginate($actividades, $page, 15));

        $view->setTemplate('application/actividad/index');
        return $view;
    }

    public function ListaAprobadasAction()
    {
        $user = $this->checkAuth([2, 3, 4]);
        if ($user instanceof Response) {
            return $user;
        }

        $actividades = $this->activityService
            ->obtenerActividadesAprobadas(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            );

        $hayPendientes = count(
            $this->activityService->obtenerActividadesPendientes(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            )
        ) > 0;

        $view = new ViewModel([
            'actividades'   => $actividades,
            'rol_id'           => $user['rol_id'],
            'tipo_id'       => $user['tipo_id'],
            'esPendientes'  => false,
            'esAprobado'    => true,
            'hayPendientes' => $hayPendientes,
        ]);

        $page = (int) $this->params()->fromQuery('page', 1);

        $view->setVariable('actividades', PaginatorHelper::paginate($actividades, $page, 15));

        $view->setTemplate('application/actividad/index');
        return $view;
    }

    public function DetalleAction()
    {
        $user = $this->checkAuth([3]);

        if ($user instanceof Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl('/actividades');
        }

        $actividad = $this->activityService->obtenerActividadPorId($id);
        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }

        //  Obtener roles desde DB
        $roles = $this->obtenerMapaRoles();

        //obtener carreras
        $mapaCarreras = $this->obtenerMapaCarreras();

        $view = new ViewModel([
            'actividad' => $actividad,
            'rol_id'    => $user['rol_id'],
            'tipo_id'   => $user['tipo_id'],
            'roles'     => $roles, // <-- pasamos roles dinámicos
            'carreras'   => $mapaCarreras,
        ]);

        $view->setTemplate('application/actividad/detalle');
        return $view;
    }

    private function obtenerMapaRoles(): array
    {
        $rolesResult = $this->rolTable->getRol() ?? [];

        $roles = [];

        foreach ($rolesResult as $rol) {
            $roles[(int)$rol->id] = $rol->nombre;
        }

        return $roles;
    }

    private function obtenerMapaCarreras(): array
    {
        $carrerasResult = $this->carreraTable->fetchAll() ?? [];

        $carreras = [];

        foreach ($carrerasResult as $carrera) {
            $carreras[(int)$carrera->carrera]
                = $carrera->nombre;
        }

        return $carreras;
    }


    public function pendientesAction() //para ver actiivdades pendientes
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $actividades = $this->activityService
            ->obtenerActividadesPendientes(
                $user['tipo_id'],
                $user['rol_id'],
                $user['carreras'],
                $user['extension']
            );

        $view = new ViewModel([
            'actividades'   => $actividades,
            'rol_id'           => $user['rol_id'],
            'tipo_id'       => $user['tipo_id'],
            'esPendientes'  => true,
            'esAprobado'    => false,
            'hayPendientes' => count($actividades) > 0,
        ]);

        $page = (int) $this->params()->fromQuery('page', 1);

        $view->setVariable('actividades', PaginatorHelper::paginate($actividades, $page, 15));

        $view->setTemplate('application/actividad/index');
        return $view;
    }

    public function definirParticipacionAction() // esto es para cuando se crea una actividad simple
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof Response) {
            return $user;
        }

        $actividad = $this->obtenerActividadPorIdDesdeRuta();

        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }

        $this->validarActividadAprobada($actividad);

        // Ya tiene participación
        if ($actividad->tipo_participacion !== null) {

            return $this->redirect()->toUrl(
                '/actividades/edit/' . $actividad->id
            );
        }

        // Mostrar form
        if (!$this->getRequest()->isPost()) {

            $view = new ViewModel([
                'actividad' => $actividad
            ]);

            $view->setTemplate(
                'application/actividad/definir-participacion'
            );

            return $view;
        }

        $connection = $this->adapter
            ->getDriver()
            ->getConnection();

        try {

            $connection->beginTransaction();

            $data  = $this->params()->fromPost();
            $files = $this->params()->fromFiles();

            $this->agregarExtensionUsuario($data, $user);

            $tipo = $data['tipo_participacion'] ?? null;

            if (!in_array($tipo, ['individual', 'lista'])) {
                throw new \Exception('Tipo inválido.');
            }

            // Definir tipo
            $this->activityService
                ->definirTipoParticipacion(
                    $actividad->id,
                    $tipo
                );

            // Reutilizar lógica
            $data['carrera'] = $actividad->carrera;

            $this->procesarParticipacion(
                $user['extension'],
                $actividad->id,
                $data,
                $files,
                false
            );

            // Aprobar automáticamente
            $lista = $this->attendanceService
                ->obtenerListaPorActividad($actividad->id);

            if ($lista) {
                $this->attendanceService
                    ->aprobarLista($lista->id);
            }

            $connection->commit();

            return $this->redirect()->toUrl('/actividades');
        } catch (\Exception $e) {

            $connection->rollback();

            $this->flashMessenger()->addErrorMessage(
                $e->getMessage()
            );

            return $this->redirect()->toUrl('/actividades');
        }
    }

    private function obtenerActividadPorIdDesdeRuta()
    {
        $id = (int)$this->params()->fromRoute('id');

        if (!$id) {
            return null;
        }

        $actividad = $this->activityService
            ->obtenerActividadPorId($id);

        if (!$actividad) {
            throw new \Exception('Actividad no existe.');
        }

        return $actividad;
    }

    private function validarActividadAprobada(object $actividad): void
    {
        if ((int)$actividad->estado !== 1) {
            throw new \Exception(
                'La actividad debe estar aprobada.'
            );
        }
    }
}
