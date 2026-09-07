<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AttendanceService;
use Application\Service\ActivityService;

use Laminas\View\Model\ViewModel;
//Controlador para usuarios del rol "asociacionAcademica".
//Extiende BaseController, lo que permite usar checkAuth() para validar permisos.

class AttendanceController extends BaseController
{
    private AttendanceService $attendanceService;
    private ActivityService $activityService;

    public function __construct(AttendanceService $attendanceService, ActivityService $activityService)
    {
        $this->attendanceService = $attendanceService;
        $this->activityService = $activityService;
    }


    //Ver estudiantes de lista
    public function viewAction()
    {
        $user = $this->checkAuth([2,3,4]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $buscar = trim($this->params()->fromQuery('buscar', ''));

        $tipo_id = (int)$user['tipo_id'];

        $actividadId = (int)$this->params()->fromRoute('id');

        if (!$actividadId) {
            return $this->redirect()->toUrl('/actividades');
        }

        // Obtener la única lista asociada a esta actividad
        $lista = $this->attendanceService->obtenerListaPorActividad($actividadId);

        if (!$lista) {
            // Si no existe lista, redirigir a actividades
            return $this->redirect()->toUrl('/actividades');
        }

        // Obtener detalle de la lista (estudiantes)
        $estudiantes = $this->attendanceService->obtenerDetalleLista($lista->id,$buscar);
        $actividad = $this->activityService->obtenerActividadPorId($actividadId);

        if (!$actividad) {
            return $this->redirect()->toUrl('/actividades');
        }
        $view = new ViewModel([
            'actividadId' => $actividadId,
            'actividadEstado' => (int) $actividad->estado,
            'listaId'     => $lista->id,
            'listaEstado' => $lista->estado,
            'estudiantes' => $estudiantes,
            'tipo_id'         => $tipo_id,
            'buscar'          => $buscar,
        ]);

        $view->setTemplate('application/attendance/view');
        return $view;
    }


}
