<?php

namespace Application\Service;

use Application\Model\ActivityTable;
use Application\Model\Activity;
use Laminas\Session\Container;
use Application\Service\MailService;
use Application\Model\UserTable;
use Application\Model\RolTable;
use Application\Model\CarreraTable;
use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;

class ActivityService
{
    private ActivityTable $activityTable;
    private Container $session;
    private MailService $mailService;
    private UserTable $userTable;
    private RolTable $rolTable;
    private CarreraTable $carreraTable;
    private AttendanceListTable $attendanceListTable;
    private AttendanceListStudentTable $attendanceListStudentTable;

    public function __construct(
        ActivityTable $activityTable,
        MailService $mailService,
        UserTable $userTable,
        RolTable $rolTable,
        AttendanceListTable $attendanceListTable,
        AttendanceListStudentTable $attendanceListStudentTable,
        CarreraTable $carreraTable
    ) {
        $this->activityTable = $activityTable;
        $this->session   = new Container('user');
        $this->mailService = $mailService;
        $this->userTable = $userTable;
        $this->rolTable = $rolTable;
        $this->carreraTable = $carreraTable;
        $this->attendanceListTable = $attendanceListTable;
        $this->attendanceListStudentTable = $attendanceListStudentTable;
    }

    private function createActivityBase(array $data, bool $requireTipoParticipacion): int
    {
        $usuario_id = $this->session->userId ?? null;
        $rol_id  = $this->session->rol_id ?? null;
        $tipo_id = $this->session->tipo_id ?? null;

        if (!$usuario_id || !$rol_id || !$tipo_id) {
            throw new \Exception("No se pudo obtener el usuario logueado.");
        }

        // Determinar organizador y carrera
        if (in_array($tipo_id, [2, 3])) {

            if (empty($data['organizador'])) {
                throw new \Exception("Debe seleccionar organizador.");
            }

            if (empty($data['carrera'])) {
                throw new \Exception("Debe seleccionar carrera.");
            }

            $organizador = (int) $data['organizador'];
            $carrera     = (int) $data['carrera'];
            
        } elseif (in_array($tipo_id, [4])) { // asociaciones

            if (empty($data['carrera'])) {
                throw new \Exception("No se pudo determinar la carrera.");
            }

            $organizador = $rol_id; // el organizador es la asociación logueada
            $carrera     = (int) $data['carrera'];
        } else {
            throw new \Exception("Rol no autorizado.");
        }

        // Validamos existencia de carrera
        $this->carreraTable->getCarrera($carrera);

        // 🔹 Validaciones obligatorias comunes
        foreach (['nombre', 'fecha', 'credito'] as $campo) {
            if (empty($data[$campo])) {
                throw new \Exception("El campo {$campo} es obligatorio.");
            }
        }

        // Validación condicional del tipo
        $tipoParticipacion = $data['tipo_participacion'] ?? null;

        if ($requireTipoParticipacion && empty($tipoParticipacion)) {
            throw new \Exception("El campo tipo_participacion es obligatorio.");
        }

        if (!isset($data['extension']) || $data['extension'] === null) {
            throw new \Exception('No se pudo determinar la extensión del usuario.');
        }

        $activity = new Activity();
        $activity->exchangeArray([
            'id'                  => null,
            'nombre'              => $data['nombre'],
            'organizador'         => $organizador,
            'fecha'               => $data['fecha'],
            'usuario_id'          => $usuario_id,
            'credito'             => $data['credito'],
            'carrera'             => $carrera,
            'tipo_participacion'  => $tipoParticipacion,
            'extension'           => $data['extension'],
            'estado'              => 0 // pendiente
        ]);

        // echo "<pre>";
        // var_dump($activity);
        // exit;

        return $this->activityTable->saveActivity($activity);
    }

    public function createActivity(array $data): int
    {
        return $this->createActivityBase($data, true);
    }

    public function createActivitySimple(array $data): int
    {
        return $this->createActivityBase($data, false);
    }


    private function tiposVisiblesPorTipo(int $tipoId): array
    {
        return match ($tipoId) {

            1 => [1, 2, 3, 4], // admin ve todo

            2 => [2], // personal solo personal

            3 => [2, 3, 4], // secretaria ve personal y asociaciones

            4 => [4], // asociaciones solo asociaciones verificar que tambien vea por carrera

            default => [],
        };
    }

    public function listarActividades(
        int $tipoId,
        array $userCarreras,
        int $userExtension,
        int $rolId,

    ) {
        $tiposPermitidos = $this->tiposVisiblesPorTipo($tipoId);

        if (empty($tiposPermitidos)) {
            return [];
        }

        return $this->activityTable->getActividadesPorTipos(
            $tiposPermitidos,
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );
    }

    public function buscarActividad(
        string $query,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $tipos = $this->tiposVisiblesPorTipo($tipoId);
        return $this->activityTable->searchActivity(
            $query,
            $tipos,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension
        );
    }

    public function obtenerActividadPorId(int $id)
    {
        return $this->activityTable->getActivityById($id);
    }

    public function buscarActividades(string $buscar) //esto se usa en reporteService
    {
        return $this->activityTable->buscarActividades($buscar);
    }

    public function obtenerActividadReporteById(int $id) //esto se usa en reporteService
    {
        return $this->activityTable->getActivityReporteById($id);
    }

    public function obtenerParticipantesPorActividad(int $actividadId) //esto se usa en reporteService
    {
        return $this->attendanceListStudentTable->getParticipantesByActividad($actividadId);
    }

    public function reporteAuditoriaEstudiantes(int $carnet = null): array
    {
        return $this->attendanceListStudentTable->fetchAuditoriaEstudiantes($carnet);
    }

    // public function fetchAuditoriaEstudiantesByOrganizacion(int $id, int $carnet = null): array
    // {
    //     return $this->attendanceListStudentTable->fetchAuditoriaEstudiantesByOrganizacion($id, $carnet);
    // }

    public function fetchAuditoriaEstudiantesByOrganizacion(array $user, int $carnet = null): array
    {
        return $this->attendanceListStudentTable->fetchAuditoriaEstudiantesByOrganizacion($user, $carnet);
    }

    public function fetchPdfEstudiante(
        int $estudianteId,
        string $modo,
        int $carreraUsuario,
        int $extensionUsuario
    ) {
        return $this->attendanceListStudentTable
            ->fetchPdfEstudiante($estudianteId, $modo, $carreraUsuario, $extensionUsuario);
    }

    public function editActivity(array $data): int
    {
        /*
        solo podemos editar una actividad cuando estado = 0, en caso contrario
        la actividad ya fue aprobada, ademas el valor de estado no aparece aca
        aparecera en  exchangeArray que hereda de Activity.php
        */
        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No se pudo obtener el usuario logueado.");
        }

        // Crear objeto Actividad actualizado
        $activity = new Activity();
        $activity->exchangeArray([
            'id'       => $data['id'], //verificar si es id o userId
            'nombre' => $data['nombre'],
            'organizador' => $data['organizador'],
            'fecha' => $data['fecha'],
            'usuario_id' => $usuario_id,
            'carrera'   => $data['carrera'],
            'credito'   => $data['credito'],
        ]);

        return $this->activityTable->editActivity($activity);
    }

    public function editActivitySimple(array $data)
    {
        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No se pudo obtener el usuario logueado.");
        }

        foreach (['nombre', 'fecha', 'credito'] as $campo) {
            if (empty($data[$campo])) {
                throw new \Exception("El campo {$campo} es obligatorio.");
            }
        }

        $activity = new Activity();
        $activity->exchangeArray([
            'id'       => $data['id'], //verificar si es id o userId
            'nombre' => $data['nombre'],
            'organizador' => $data['organizador'],
            'fecha' => $data['fecha'],
            'usuario_id' => $usuario_id,
            'carrera'   => $data['carrera'],
            'credito'   => $data['credito'],
        ]);

        return $this->activityTable->editActivity($activity);
    }


    public function definirTipoParticipacion(int $id, string $tipo)
    {
        $actividad = $this->activityTable->getActivityById($id);
        $actividad->tipo_participacion = $tipo;

        return $this->activityTable->saveActivity($actividad);
    }




    public function ActualizarActividad(int $id, int $aprobadoPor): void
    {
        //Obtener actividad
        $actividad = $this->obtenerActividadPorId($id);

        if (!$actividad) {
            throw new \Exception('La actividad no existe.');
        }

        // Obtener usuario creador
        $usuario = $this->userTable->getUserById($actividad->usuario_id);

        if (!$usuario) {
            throw new \Exception('Usuario responsable no encontrado.');
        }

        //obtener usaurio que aprueba la actividad
        $UsuarioAprobador = $this->userTable->getUserById($aprobadoPor);

        //obtener el rol del usuario aprobador
        $rol = $this->rolTable->getRolById($UsuarioAprobador->rol_id);
        if (!$rol) {
            throw new \Exception('Rol del aprobador no encontrado.');
        }

        // Aprobar actividad
        $this->activityTable->updateActivity($id, $aprobadoPor);

        // Enviar correo
        $subject = 'Actividad aprobada';
        $message =
            "Hola {$usuario->nombre} {$usuario->apellido},\n\n"
            . "Tu actividad '{$actividad->nombre}' ha sido aprobada.\n\n"
            . "Aprobada por: {$rol->nombre}\n\n"
            . "Fecha: " . date('d/m/Y') . "\n\n"
            . "Saludos.";

        $this->mailService->send(
            $usuario->correo,
            $subject,
            $message
        );
    }


    public function RechazarActividad(int $id, string $motivo, int $rechazadoPor): void
    {

        // Obtener actividad
        $actividad = $this->obtenerActividadPorId($id);

        if (!$actividad) {
            throw new \Exception('La actividad no existe.');
        }

        // Obtener usuario creador
        $usuario = $this->userTable->getUserById($actividad->usuario_id);

        if (!$usuario) {
            throw new \Exception('Usuario responsable no encontrado.');
        }

        $UsuarioRechazador = $this->userTable->getUserById($rechazadoPor);

        //obtener el rol del usuario aprobador
        $rol = $this->rolTable->getRolById($UsuarioRechazador->rol_id);
        if (!$rol) {
            throw new \Exception('Rol del aprobador no encontrado.');
        }

        //Rechazar actividad (BD)
        $this->activityTable->rechazoActivity($id, $motivo, $rechazadoPor);

        //rechazar lista en db
        $this->attendanceListTable->rejectByActividad(
            $id
        );

        // Enviar correo de rechazo
        $subject = 'Actividad No realizada';
        $message = "Hola {$usuario->nombre} {$usuario->apellido},\n\n"
            . "Tu actividad '{$actividad->nombre}' no se ha realizado.\n\n"
            . "Rechazada por: {$rol->nombre}\n"
            . "Fecha: " . date('d/m/Y') . "\n\n"
            . "Motivo de la actividad no realizada:\n{$motivo}\n\n"
            . "Saludos.";

        $this->mailService->send(
            $usuario->correo,
            $subject,
            $message
        );
    }


    public function obtenerActividadesPendientes(
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $tiposPermitidos = $this->tiposVisiblesPorTipo($tipoId);
        return $this->activityTable->getActividadesPendientesPorTipos(
            $tiposPermitidos,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension
        );
    }

    public function obtenerActividadesAprobadas(
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $tipos = $this->tiposVisiblesPorTipo($tipoId);

        return $this->activityTable
            ->getActividadesPorEstadoYTipos(
                1,
                $tipos,
                $tipoId,
                $rolId,
                $userCarreras,
                $userExtension
            );
    }

    public function obtenerActividadesRechazadas(
        int $tipoId,
        array $userCarreras,
        int $userExtension,
        string $buscar = ''
    ) {
        return $this->activityTable
            ->getActividadesPorEstado(
                2,
                $tipoId,
                $userCarreras,
                $userExtension,
                $buscar
            );
    }

    public function obtenerTodasLasActividades(
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $tipos = $this->tiposVisiblesPorTipo($tipoId);

        return $this->activityTable->fetchAllPorRoles(
            $tipos,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension
        );
    }

    public function detallesActividad(array $data): int
    {
        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No se pudo obtener el usuario logueado.");
        }

        // Crear objeto Actividad actualizado
        $activity = new Activity();
        $activity->exchangeArray([
            'id'       => $data['id'], //verificar si es id o userId
            'nombre' => $data['nombre'],
            'organizador' => $data['organizador'],
            'fecha' => $data['fecha'],
            'usuario_id' => $usuario_id,
            'carrera'   => $data['carrera'],
            'credito'   => $data['credito'],
        ]);

        return $this->activityTable->editActivity($activity);
    }

    public function getConteoActividadesPorEstado(array $user): array
    {
        return $this->activityTable->getConteoActividadesPorEstado($user);
    }

    public function getActividadesPorMes(
        array $user,
        array $filters = []
    ): array {
        return $this->activityTable->getActividadesPorMes($user, $filters);
    }

    public function getAprobadasVsPendientes(
        array $user,
        array $filters = []
    ): array {
        return $this->activityTable->getAprobadasVsPendientes($user, $filters);
    }

    public function getTopActividades(
        array $user,
        array $filters = []
    ): array {
        return $this->activityTable->getTopActividades($user, $filters);
    }

    public function getTiempoPromedioResolucion(array $user): float
    {
        return $this->activityTable->getTiempoPromedioResolucion($user);
    }

    public function getTopUsuarios(
        array $user,
        array $filters = []
    ): array {
        return $this->activityTable->getTopUsuarios($user, $filters);
    }

    public function getActividadesPorCarrera(
        array $user,
        array $filters = []
    ): array {

        $data = $this->activityTable
            ->getActividadesPorCarrera($user, $filters);

        // catálogo desde SATU
        $carrerasMap = $this->carreraTable
            ->getCarrerasMap();

        foreach ($data as &$item) {

            $codigo = $item['carrera'];

            $item['nombre'] =
                $carrerasMap[$codigo]
                ?? 'Carrera desconocida';
        }

        return $data;
    }
}
