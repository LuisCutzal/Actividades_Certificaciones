<?php

namespace Application\Service;

use Application\Model\StudentTable;
use Application\Model\AttendanceListStudentTable;
use Application\Model\CarreraEstudianteTable;

class EstudianteService
{
    private StudentTable $studentTable;
    private AttendanceListStudentTable $attendanceListStudentTable;
    private CarreraEstudianteTable $carreraEstudianteTable;

    public function __construct(StudentTable $studentTable, AttendanceListStudentTable $attendanceListStudentTable, CarreraEstudianteTable $carreraEstudianteTable)
    {
        $this->studentTable = $studentTable;
        $this->attendanceListStudentTable = $attendanceListStudentTable;
        $this->carreraEstudianteTable = $carreraEstudianteTable;
    }

    public function listarTodos()
    {
        return $this->studentTable->fetchAll();
    }

    public function obtenerPorCarnet(int $carnet)
    {
        return $this->studentTable->getByCarnet($carnet);
    }

    public function obtenerEstudiante(int $carnet)
    {
        return $this->studentTable->getByCarnet($carnet);
    }

    public function obtenerActividadesDelEstudiante(int $idStudent, string $buscar = '')
    {
        return $this->attendanceListStudentTable
            ->getActividadesByStudent($idStudent, $buscar);
    }


    public function listarPorCarreras(array $carreras, int $extension)
    {
        if (empty($carreras)) {
            return [];
        }

        $carnets = $this->carreraEstudianteTable
            ->getCarnetsPorCarrera($carreras);

        return $this->studentTable->fetchByCarnets($carnets, $extension, $carreras);
    }

    public function listarPorExtension(int $extension)
    {
        return $this->studentTable
            ->fetchByExtension($extension);
    }


    public function buscarPorTipo(
        string $query,
        int $tipoId,
        array $carreras,
        int $extension
    ) {
        $existe = $this->studentTable->existePorNombreOCarnet($query);

        if (!$existe) {
            return [
                'estudiantes' => [],
                'mensaje' => 'El estudiante no existe.'
            ];
        }

        // personal administrativo (tipo 2)
        if ($tipoId === 2) {
            return [
                'estudiantes' => $this->studentTable->buscarPorNombreOCarnet(
                    $query,
                    $extension
                ),
                'mensaje' => null
            ];
        }

        if (empty($carreras)) {
            return [
                'estudiantes' => [],
                'mensaje' => 'No tiene carreras asignadas.'
            ];
        }

        $carnets = $this->carreraEstudianteTable
            ->getCarnetsPorCarrera($carreras);

        $estudiantes = $this->studentTable
            ->buscarPorNombreOCarnetYCarnets(
                $query,
                $carnets,
                $carreras,
                $extension
            );

        if (empty($estudiantes)) {
            return [
                'estudiantes' => [],
                'mensaje' => 'El estudiante existe, pero pertenece a otra carrera.'
            ];
        }

        return [
            'estudiantes' => $estudiantes,
            'mensaje' => null
        ];
    }

    public function existePorCarnet(int $carnet): bool
    {
        return $this->obtenerPorCarnet($carnet) !== null;
    }
}
