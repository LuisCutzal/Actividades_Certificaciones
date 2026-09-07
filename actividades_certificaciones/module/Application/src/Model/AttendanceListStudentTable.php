<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Expression;


class AttendanceListStudentTable
{
    private TableGatewayInterface $tableGateway;
    private string $schema;

    public function __construct(TableGatewayInterface $tableGateway, string $schema)
    {
        $this->tableGateway = $tableGateway;
        $this->schema = $schema;
    }

    public function insert(array $data)
    {
        $this->tableGateway->insert($data);
    }

    public function getByList(int $idList): array
    {
        return $this->tableGateway
            ->select(['id_attendance_list' => $idList])
            ->toArray();
    }

    public function delete(array $where)
    {
        return $this->tableGateway->delete($where);
    }

    public function getDetalleLista(int $listaId): array
    {
        return $this->tableGateway->select(function (Select $select) use ($listaId) {

            // Solo lo que necesitas
            $select->columns(['carnet']);

            $select->where([
                'id_attendance_list' => $listaId
            ]);
        })->toArray();
    }

    public function fetchByLista(int $listaId): ?array
    {
        $rowset = $this->tableGateway->select(['id_attendance_list' => $listaId]);
        $row = $rowset->current();
        return $row ? (array) $row : null;
    }

    public function update(array $data, array $where): int
    {
        // $data → los campos que quieres actualizar
        // $where → condiciones para seleccionar los registros (ej: ['id' => 123])
        return $this->tableGateway->update($data, $where);
    }

    public function getActividadesByStudent(int $idStudent, string $buscar = ''): array
    {
        $select = new Select('attendance_list_student');

        $select->join(
            ['al' => 'attendance_list'],
            'al.id = attendance_list_student.id_attendance_list',
            ['estado'],
            Select::JOIN_INNER
        );

        $select->join(
            ['a' => 'actividad'],
            'a.id = al.id_actividad',
            [
                'actividad_nombre' => 'nombre',
                'fecha',
                'credito',
            ],
            Select::JOIN_INNER
        );

        $this->joinOrganizadorDesdeUsuario($select);

        $select->where([
            'attendance_list_student.carnet' => $idStudent
        ]);

        $select->where([
            'attendance_list_student.carnet' => $idStudent
        ]);

        if ($buscar !== '') {
            $select->where->nest()
                ->like('a.nombre', "%$buscar%")
                ->or
                ->like('r.nombre', "%$buscar%")
                ->unnest();
        }

        $select->order('a.fecha DESC');

        // FORZAR RESULTADO COMO ARRAY
        $resultSet = $this->tableGateway->selectWith($select);

        return iterator_to_array($resultSet);
    }

    //esto es para el reporte de estudiantes por actividad que hace el administrador

    public function getParticipantesByActividad(int $actividadId): array
    {
        $select = new Select('attendance_list_student');

        $select->join(
            ['al' => 'attendance_list'],
            'al.id = attendance_list_student.id_attendance_list',
            [
                'estado_lista' => 'estado',
                'creado',
                'aprobado',
                'rechazado',
            ],
            Select::JOIN_INNER
        );

        $select->join(
            ['a' => 'actividad'],
            'a.id = al.id_actividad',
            [
                'actividad_id' => 'id',
                'actividad_nombre' => 'nombre',
                'tipo_participacion',
                'motivo_rechazo',
                'fecha_resolucion',
            ],
            Select::JOIN_INNER
        );

        $this->joinCarrera($select);

        $this->joinEstudiante(
            $select,
        );

        $select->where([
            'a.id' => $actividadId
        ]);

        $select->order('e.nombre ASC');

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }

    //esto es para auditar al estudiante por parte del admin
    public function fetchAuditoriaEstudiantes(?int $carnet = null): array
    {
        $select = new Select('attendance_list_student');

        $select->join(
            ['al' => 'attendance_list'],
            'al.id = attendance_list_student.id_attendance_list',
            [
                'estado_lista' => 'estado',
                'creado',
                'aprobado',
                'rechazado',
            ],
            Select::JOIN_INNER
        );

        $select->join(
            ['a' => 'actividad'],
            'a.id = al.id_actividad',
            [
                'actividad_nombre'  => 'nombre',
                'organizador',
                'tipo_participacion',
                'credito',
                'estado_actividad'  => 'estado',
                'fecha_actividad'   => 'fecha',
                'fecha_resolucion',
                'motivo_rechazo',
            ],
            Select::JOIN_INNER
        );

        $this->joinCarrera($select);

        $this->joinRolOrganizador($select);
        $this->joinEstudiante(
            $select,
            'estudiante_nombre'
        );

        // orden
        $select->order([
            'al.creado DESC'
        ]);

        if (!empty($carnet)) {

            $select->where->like(
                'attendance_list_student.carnet',
                '%' . $carnet . '%'
            );
        }

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }

    public function fetchAuditoriaEstudiantesByOrganizacion(array $user, ?int $carnet = null): array
    {
        $select = new Select('attendance_list_student');

        $select->join(
            ['al' => 'attendance_list'],
            'al.id = attendance_list_student.id_attendance_list',
            [
                'estado_lista' => 'estado',
                'creado',
                'aprobado',
                'rechazado',
            ]
        );

        $select->join(
            ['a' => 'actividad'],
            'a.id = al.id_actividad',
            [
                'actividad_nombre' => 'nombre',
                'tipo_participacion',
                'credito',
                'fecha_actividad' => 'fecha',
            ]
        );


        $this->joinCarrera($select);

        $this->joinRolOrganizador($select);

        $this->joinEstudiante(
            $select,
            'estudiante_nombre'
        );

        $select->where->in(
            'a.carrera',
            $user['carreras']
        );

        if (!empty($carnet)) {
            $select->where->like(
                'attendance_list_student.carnet',
                '%' . $carnet . '%'
            );
        }

        $select->order('al.creado DESC');

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }

    //esto es para los reportes de asociaciones de las actividades por parte de las asociaciones
    public function fetchPdfEstudiante(
        int $estudianteId,
        string $modo,
        int $carreraUsuario,
        int $extensionUsuario
    ): array {

        $select = new Select('attendance_list_student');

        $select->join(
            ['al' => 'attendance_list'],
            'al.id = attendance_list_student.id_attendance_list',
            [
                'estado_lista' => 'estado',
            ]
        );

        $select->join(
            ['a' => 'actividad'],
            'a.id = al.id_actividad',
            [
                'actividad' => 'nombre',
                'credito',
                'fecha',
            ]
        );

        $select->where([
            'attendance_list_student.carnet' => $estudianteId,

            'a.carrera' => $carreraUsuario,
            'a.extension' => $extensionUsuario,
        ]);

        if ($modo === 'aprobadas') {

            $select->where([
                'al.estado' => 1
            ]);
        } else {

            $select->where->in(
                'al.estado',
                [0, 1]
            );
        }

        $select->order('a.fecha ASC');

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }

    public function getCarnetsPorLista(
        int $listaId
    ): array {

        $resultSet = $this->tableGateway->select(
            function (Select $select) use ($listaId) {

                $select->columns(['carnet']);

                $select->where([
                    'id_attendance_list' => $listaId
                ]);
            }
        );
        return iterator_to_array($resultSet);
    }

    private function joinOrganizadorDesdeUsuario(
        Select $select
    ): void {

        $select->join(
            ['u' => 'usuarios'],
            'u.id = a.usuario_id',
            [],
            Select::JOIN_LEFT
        );

        $select->join(
            ['r' => 'roles'],
            'r.id = u.rol_id',
            [
                'organizador_nombre' => 'nombre'
            ],
            Select::JOIN_LEFT
        );
    }

    private function joinRolOrganizador(
        Select $select
    ): void {

        $select->join(
            ['r' => 'roles'],
            'r.id = a.organizador',
            [
                'organizador_nombre' => 'nombre'
            ],
            Select::JOIN_LEFT
        );
    }

    private function joinCarrera(Select $select): void
    {
        $select->join(
            ['c' => new Expression($this->schema . '.carrera')],
            'c.carrera = a.carrera',
            ['carrera_nombre' => 'nombre'],
            Select::JOIN_LEFT
        );
    }

    private function joinEstudiante(
        Select $select,
        string $aliasNombre = 'nombre'
    ): void {

        $select->join(
            ['e' => new Expression($this->schema . '.estudiante')],
            'e.carnet = attendance_list_student.carnet',
            [
                'carnet' => new Expression('attendance_list_student.carnet'),
                $aliasNombre => new Expression('e.nombre'),
            ],
            Select::JOIN_LEFT
        );
    }
}
