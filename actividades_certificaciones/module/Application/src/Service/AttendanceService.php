<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;
use Application\Model\StudentAttendanceTable;
use Application\Model\StudentTable;
use Application\Model\CarreraEstudianteTable;
use Application\Model\InscripcionTable;
use Application\Service\EstudianteService;

use Application\Service\ActivityService;

use Laminas\Session\Container;

use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceService
{
    private AttendanceListTable $attendanceListTable;
    private AttendanceListStudentTable $attendanceListStudentTable;
    private StudentAttendanceTable $studentAttendanceTable;
    private StudentTable $studentTable;
    private ActivityService $activityService;
    private Container $session;
    private CarreraEstudianteTable $carreraEstudianteTable;
    private InscripcionTable $inscripcionTable;
    private EstudianteService $estudianteService;

    public function __construct(
        AttendanceListTable $attendanceListTable,
        AttendanceListStudentTable $attendanceListStudentTable,
        StudentAttendanceTable $studentAttendanceTable,
        StudentTable $studentTable,
        ActivityService $activityService,
        CarreraEstudianteTable $carreraEstudianteTable,
        InscripcionTable $inscripcionTable,
        EstudianteService $estudianteService
    ) {
        $this->attendanceListTable = $attendanceListTable;
        $this->attendanceListStudentTable = $attendanceListStudentTable;
        $this->studentAttendanceTable = $studentAttendanceTable;
        $this->studentTable = $studentTable;
        $this->inscripcionTable = $inscripcionTable;
        $this->carreraEstudianteTable = $carreraEstudianteTable;
        $this->activityService = $activityService;
        $this->estudianteService = $estudianteService;

        $this->session = new Container('user');
    }

    //lista de asistencia desde el archivo que se carga
    public function crearLista(int $idActividad, array $file): array
    {
        if (!$this->activityService->obtenerActividadPorId($idActividad)) {
            throw new \Exception("La actividad no existe.");
        }

        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No hay usuario autenticado.");
        }

        if (empty($file['tmp_name'])) {
            throw new \Exception("Debe subir un archivo de lista.");
        }

        $carnets = $this->leerArchivoCarnets($file);
        if (count($carnets) === 0) {
            throw new \Exception("El archivo no contiene carnets válidos.");
        }

        $listaCarnets = array_column($carnets, 'carnet');
        $duplicados   = array_diff_assoc($listaCarnets, array_unique($listaCarnets));

        if (!empty($duplicados)) {
            throw new \Exception(
                'El archivo contiene carnets duplicados: ' .
                    implode(', ', array_unique($duplicados))
            );
        }

        $listaId = $this->attendanceListTable->insert([ //aca insertamos en la tabla attendance_list
            'id_actividad' => $idActividad,
            'id_usuario'   => $usuario_id,
            'estado'       => 0,
            'creado'       => date('Y-m-d H:i:s'),
            'aprobado'     => null
        ]);

        return [
            'listaId' => $listaId,
            'carnets' => $carnets
        ];
    }

    public function crearListaVacia(int $actividadId): int
    {
        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No hay usuario autenticado.");
        }

        return $this->attendanceListTable->insert([
            'id_actividad' => $actividadId,
            'id_usuario'   => $usuario_id,
            'estado'       => 0,
            'creado'       => date('Y-m-d H:i:s'),
            'aprobado'     => null
        ]);
    }


    //leemos el archivo el cual sera solo csv
    private function leerArchivoCarnets(array $file): array
    {
        $nombreOriginal = $file['name'];      // ejemplo: lista.csv
        $rutaTemporal   = $file['tmp_name'];  // /tmp/php92jfsd

        // Obtener extensión real del nombre original
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        $permitidos = ['xlsx', 'xls', 'xml'];

        if (!in_array($ext, $permitidos)) {
            throw new \Exception("Formato no soportado. Solo Excel (.xlsx, .xls, .xml).");
        }

        return $this->leerExcel($rutaTemporal);
    }

    //leemos csv
    private function leerExcel(string $ruta): array //aun faltan validaciones, para verificar si los datos son correctos
    {
        $estudiantes = [];

        try {

            $spreadsheet = IOFactory::load($ruta);
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();

            if (count($rows) < 2) {
                throw new \Exception(
                    "El archivo no contiene datos válidos."
                );
            }

            $encabezados = $rows[0];

            $encabezado = strtolower(trim($encabezados[0]));
            $encabezado = str_replace('é', 'e', $encabezado);

            if (!in_array($encabezado, ['carne', 'carnet', 'carnets'])) {
                throw new \Exception(
                    "El archivo debe tener 'Carné' en el encabezado de la primera columna."
                );
            }
            if (
                strtolower(trim($encabezados[1])) !== 'nombre y apellido' &&
                strtolower(trim($encabezados[1])) !== 'nombres y apellidos'
            ) {
                throw new \Exception(
                    "El archivo debe tener 'Nombres y Apellidos' en el encabezado de la Segunda columna."
                );
            }

            // Saltar encabezado
            foreach ($rows as $index => $row) {

                if ($index === 0) {
                    continue;
                }

                // Columnas esperadas:
                // A = carnet
                // B = nombre y apellido
                // C = carrera
                // D = actividad
                // E = fecha
                // F = organizador

                $carnet = trim($row[0] ?? '');
                $nombre = trim($row[1] ?? '');

                if (!ctype_digit($carnet) || $nombre === '') {
                    continue;
                }

                $estudiantes[] = [
                    'carnet' => (int)$carnet,
                    'nombre' => $nombre
                ];
            }
        } catch (\Exception $e) {

            throw new \Exception(
                "Error al leer el archivo Excel: " .
                    $e->getMessage()
            );
        }

        return $estudiantes;
    }

    //obtenemos listas de una actividad
    public function obtenerListasPorActividad(int $idActividad): array
    {
        return $this->attendanceListTable->getByActivity($idActividad);
    }

    //botenemos detalles de la lista
    public function obtenerDetalleLista(
        int $listaId,
        string $buscar = ''
    ): array {

        //  obtener carnets desde BD local
        $rows = $this->attendanceListStudentTable
            ->getCarnetsPorLista($listaId);

        if (empty($rows)) {
            return [];
        }

        $carnets = array_column(
            $rows,
            'carnet'
        );

        //  obtener nombres desde SATU
        $estudiantes = $this->studentTable
            ->getPorCarnets(
                $carnets
            );

        //  indexar por carnet
        $mapa = [];

        foreach ($estudiantes as $est) {

            $mapa[$est['carnet']] =
                $est['nombre'];
        }

        // unir resultados
        $resultado = [];

        foreach ($rows as $row) {

            $resultado[] = [
                'carnet' => $row['carnet'],
                'nombre' => $mapa[$row['carnet']]
                    ?? 'No encontrado'
            ];
        }
        
        if ($buscar !== '') {

            $resultado = array_filter(
                $resultado,
                function ($item) use ($buscar) {

                    return str_contains(
                        (string)$item['carnet'],
                        $buscar
                    ) ||
                        stripos(
                            $item['nombre'],
                            $buscar
                        ) !== false;
                }
            );

            $resultado = array_values($resultado);
        }
        
        return $resultado;
    }

    public function obtenerLista(int $listaId)
    {
        return $this->attendanceListStudentTable->getByList($listaId);
    }

    //aprobamos lista
    public function aprobarLista(int $listaId): void
    {
        $lista = $this->attendanceListTable->find($listaId);

        if (!$lista) {
            throw new \Exception("La lista no existe.");
        }
        if ($lista->estado != 0) {
            throw new \Exception("Solo se pueden aprobar listas pendientes.");
        }

        $estudiantes = $this->attendanceListStudentTable->getByList($listaId);
        if (empty($estudiantes)) {
            throw new \Exception("No hay estudiantes asociados a esta lista.");
        }

        // Insertar asistencia real para cada estudiante
        foreach ($estudiantes as $row) {
            $studentId = $row['carnet'];

            // Evitar duplicados
            if ($this->studentAttendanceTable->exists($studentId, $lista->id_actividad)) {
                continue;
            }

            $this->studentAttendanceTable->insert([
                'carnet'         => $studentId,
                'id_activity'        => $lista->id_actividad,
                'id_attendance_list' => $listaId
            ]);
        }
        // Actualizar estado lista
        $this->attendanceListTable->updateStatus($listaId, 1); // 1 = aprobada

        // Marcar fecha aprobación
        $this->attendanceListTable->update([
            'aprobado' => date('Y-m-d H:i:s')
        ], [
            'id' => $listaId
        ]);
    }

    public function obtenerListaPorActividad(int $actividadId)
    {
        return $this->attendanceListTable->fetchByActivity($actividadId);
    }


    public function registrarEstudianteIndividual(int $actividadId, int $carnet): void
    {
        // Validar actividad
        $actividad = $this->activityService->obtenerActividadPorId($actividadId);
        if (!$actividad) {
            throw new \Exception("La actividad no existe.");
        }

        // Buscar estudiante
        $est = $this->studentTable->getByCarnet($carnet);

        // Si no existe → crearlo -> esta parte tengo que averiguar porque si no esto esta mal
        if (!$est) {
            $studentId = $this->studentTable->insert([
                'carnet' => $carnet,
                'nombre' => 'Desconocido',
                'activo' => 1
            ]);
        } else {
            $studentId = $est->carnet;
        }

        // Crear lista de asistencia (pendiente)
        $usuario_id = $this->session->userId ?? null;
        if (!$usuario_id) {
            throw new \Exception("No hay usuario autenticado.");
        }

        $listaId = $this->attendanceListTable->insert([
            'id_actividad' => $actividadId,
            'id_usuario'   => $usuario_id,
            'estado'       => 0,
            'creado'       => date('Y-m-d H:i:s'),
            'aprobado'     => null
        ]);

        // Asociar estudiante a la lista
        $this->attendanceListStudentTable->insert([
            'id_attendance_list' => $listaId,
            'carnet'         => $studentId
        ]);
    }

    public function obtenerEstudiantePorActividad(int $actividadId): ?array
    {
        // Obtener listas de la actividad
        $listas = $this->attendanceListTable->getByActivity($actividadId);

        if (empty($listas)) {
            return null;
        }

        $lista = end($listas);
        $listaId = $lista['id'];

        // Obtener estudiantes de la lista
        $estudiantes = $this->attendanceListStudentTable->getDetalleLista($listaId);
        //se modifico la funcion ahora ya no me trae el nombre ahora solo es el carnet

        if (empty($estudiantes)) {
            return null;
        }

        // Retornar el primero (único)
        return $estudiantes[0];
    }

    public function reemplazarLista(int $idActividad, array $archivo): void
    {
        // Obtener la última lista (activa) de la actividad
        $listaVieja = $this->obtenerListaPorActividad($idActividad);

        if ($listaVieja) {
            // Marcarla como reemplazada / inactiva
            $this->attendanceListTable->update(
                ['estado' => 2], // 2 = reemplazada / inactiva
                ['id' => $listaVieja->id]
            );
        }

        //Crear nueva lista y estudiantes
        $this->crearLista($idActividad, $archivo);
    }

    public function eliminarLista(int $idLista): void
    {
        // Eliminar los registros de estudiantes asociados
        $this->studentAttendanceTable->delete([
            'id_attendance_list' => $idLista
        ]);

        //Eliminar la lista de asistencia
        $this->attendanceListTable->delete([
            'id' => $idLista
        ]);
    }

    public function actualizarEstudianteIndividual(int $actividadId, int $nuevoCarnet): void
    {
        //Obtener la lista de asistencia de la actividad
        $lista = $this->attendanceListTable->fetchByActividad($actividadId);

        if (!$lista) {
            throw new \Exception("No hay lista registrada para esta actividad.");
        }

        //  Obtener el estudiante asociado a esa lista
        $listaStudent = $this->attendanceListStudentTable->fetchByLista($lista['id']);
        if (!$listaStudent) {
            throw new \Exception("No hay estudiante asociado a esta actividad.");
        }

        //  Verificar si el nuevo carnet ya existe en la tabla de estudiantes
        $estudianteExistente = $this->studentTable->getByCarnet($nuevoCarnet);

        if ($estudianteExistente) {
            // Ya existe → simplemente actualizar la relación de la lista
            $nuevoStudentId = $estudianteExistente->carnet;
        } else {
            // No existe → crear nuevo estudiante -> esta parte tengo que verificar porque si no esto esta mal
            $nuevoStudentId = $this->studentTable->insert([
                'carnet' => $nuevoCarnet,
                'nombre' => 'Desconocido', //de momento desconocido
                'activo' => 1
            ]);
        }

        //  Actualizar la relación lista-estudiante para esta actividad
        $this->attendanceListStudentTable->update(
            ['carnet' => $nuevoStudentId],
            ['id' => $listaStudent['id']]  // solo esta fila se actualiza
        );
    }

    public function registrarEstudianteEnActividad( //esto es para individuales
        int $actividadId,
        int $carnet,
        int $carreraActividad,
        int $extension,
        int $listaId,
        bool $esHistorico = false
    ): void {

        //validamos que exista el carnet
        $estudiante = $this->estudianteService->existePorCarnet($carnet);

        if (!$estudiante) {
            throw new \Exception(
                "El carnet $carnet no existe en el sistema de estudiantes."
            );
        }

        $carrerasEstudiante = $this->carreraEstudianteTable->getCarrerasPorEstudiante($carnet);

        if (empty($carrerasEstudiante)) {
            throw new \Exception("El estudiante $carnet no tiene carrera asignada");
        }

        if (!in_array($carreraActividad, $carrerasEstudiante)) {

            if (count($carrerasEstudiante) === 1) {
                throw new \Exception(
                    "El estudiante $carnet pertenece a otra carrera"
                );
            }

            throw new \Exception(
                "El estudiante $carnet no pertenece a la carrera seleccionada"
            );
        }
        if (!$esHistorico) {
            if (!$this->inscripcionTable->estudianteInscrito($carnet, $carreraActividad, $extension)) {
                throw new \Exception(
                    "El estudiante $carnet no está inscrito este año en la carrera seleccionada."
                );
            }
        }

        $this->attendanceListStudentTable->insert([
            'id_attendance_list' => $listaId,
            'carnet' => $carnet
        ]);
    }

    public function registrarEstudiantesMasivo(
        int $actividadId,
        array $carnets,
        int $carreraActividad,
        int $extension,
        int $listaId,
        bool $esHistorico = false
    ): void {

        $errores = [];

        foreach ($carnets as $item) {

            $carnet = (int)$item['carnet'];

            try {
                $this->registrarEstudianteEnActividad(
                    $actividadId,
                    $carnet,
                    $carreraActividad,
                    $extension,
                    $listaId,
                    $esHistorico
                );
            } catch (\Exception $e) {
                $errores[] = [
                    'carnet' => $carnet,
                    'error'  => $e->getMessage()
                ];
            }
        }

        if (!empty($errores)) {

            $erroresAgrupados = [];

            foreach ($errores as $item) {

                $erroresAgrupados[$item['error']][] =
                    $item['carnet'];
            }

            $msg  = 'Hay ' . count($errores) .
                ' estudiantes que no cumplen los requisitos:' . PHP_EOL;

            foreach ($erroresAgrupados as $error => $carnetsLista) {

                $lista = array_slice($carnetsLista, 0, 5);

                $msg .= $error . PHP_EOL;
                $msg .= implode(', ', $lista);

                if (count($carnetsLista) > 20) {
                    $msg .= '...';
                }

                $msg .= PHP_EOL;
            }

            $msg .= 'Revise la lista de asistencia y vuelva a intentarlo.';

            if (count($errores) > 5) {
                $msg .= PHP_EOL . '...';
            }

            throw new \Exception($msg);
        }
    }
}
