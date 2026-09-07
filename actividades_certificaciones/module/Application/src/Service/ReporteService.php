<?php

namespace Application\Service;

use Application\Service\ActivityService;
use Application\Service\EstudianteService;

use Application\Model\StudentTable;
use Application\Model\Student;
//use Application\Model\InscripcionTable;
use Application\Model\CarreraTable;
use Application\Model\RolTable;

class ReporteService
{
    private ActivityService $activityService;
    private StudentTable $studentTable;
    private EstudianteService $estudianteService;
    //private InscripcionTable $inscripcionTable;
    private CarreraTable $carreraTable;
    private RolTable $rolTable;

    public function __construct(ActivityService $activityService, StudentTable $studentTable, EstudianteService $estudianteService, /*InscripcionTable $inscripcionTable, */ CarreraTable $carreraTable, RolTable $rolTable)
    {
        $this->activityService = $activityService;
        $this->studentTable = $studentTable;
        $this->estudianteService = $estudianteService;
        //$this->inscripcionTable = $inscripcionTable;
        $this->carreraTable = $carreraTable;
        $this->rolTable = $rolTable;
    }

    public function reporteActividades(
        int $tipoId,
        array $userCarreras,
        int $userExtension,
        int $rolId
    ) {
        return $this->activityService->listarActividades(
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );
    }
    public function buscarActividades(string $buscar)
    {
        return $this->activityService->buscarActividades($buscar);
    }

    public function reporteActividad(int $id)
    {
        return $this->activityService->obtenerActividadReporteById($id);
    }

    public function reporteParticipantesActividad(int $actividadId)
    {
        return $this->activityService->obtenerParticipantesPorActividad($actividadId);
    }

    public function reporteAuditoriaEstudiantes(int $carnet = null)
    {
        return $this->activityService->reporteAuditoriaEstudiantes($carnet);
    }

    // public function fetchAuditoriaEstudiantesByOrganizacion(int $id, int $carnet = null)
    // {
    //     return $this->activityService->fetchAuditoriaEstudiantesByOrganizacion($id, $carnet);
    // }
    public function fetchAuditoriaEstudiantesByOrganizacion(array $user, int $carnet = null)
    {
        return $this->activityService->fetchAuditoriaEstudiantesByOrganizacion($user, $carnet);
    }

    public function fetchPdfEstudiante(
        int $estudianteId,
        string $modo,
        int $carreraUsuario,
        int $extensionUsuario
    ) {
        return $this->activityService
            ->fetchPdfEstudiante($estudianteId, $modo, $carreraUsuario, $extensionUsuario);
    }

    public function obtenerDatosEstudiante(int $carnet): ?Student
    {
        return $this->studentTable->getByCarnet($carnet);
    }

    public function existePorCarnet(int $idEstudiante)
    {
        return $this->estudianteService->existePorCarnet($idEstudiante);
    }

    public function getCarrera(int $id)
    {
        return $this->carreraTable->getCarrera($id);
    }

    public function getRol(int $id) 
    {
        return $this->rolTable->getRolById($id);
    }
}
