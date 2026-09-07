<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Where;

class ActivityTable
{
    private TableGatewayInterface $tableGateway;
    private string $schema;

    public function __construct(TableGatewayInterface $tableGateway, string $schema)
    {
        $this->tableGateway = $tableGateway;
        $this->schema = $schema;
    }

    private function getActivityObject(?object $row): ?Activity
    {
        if (!$row) {
            return null;
        }

        $activity = new Activity();
        $activity->exchangeArray((array) $row);
        return $activity;
    }

    private function applyCommonJoins(
        Select $select,
        bool $joinCarrera = true
    ) {

        $select->join(
            'usuarios',
            'usuarios.id = actividad.usuario_id',
            [],
            Select::JOIN_LEFT
        );

        $select->join(
            ['r' => 'roles'],
            'r.id = usuarios.rol_id',
            [
                'organizador_nombre' => 'nombre',
                'tipo_id'
            ],
            Select::JOIN_LEFT
        );

        if ($joinCarrera) {

            $select->join(
                ['c' => new Expression($this->schema . '.carrera')],
                'c.carrera = actividad.carrera',
                ['carrera_nombre' => 'nombre'],
                Select::JOIN_LEFT
            );
        }
    }

    private function applySearchFilter(Select $select, string $buscar, string $alias)
    {
        if ($buscar !== '') {
            $select->where->nest()
                ->like("$alias.nombre", "%$buscar%")
                ->or
                ->like('r.nombre', "%$buscar%")
                ->or
                ->like("$alias.motivo_rechazo", "%$buscar%")
                ->unnest();
        }
    }

    public function getActivityById(int $id): ?Activity
    {
        return $this->getActivityObject(
            $this->tableGateway->select(['id' => $id])->current()
        );
    }

    public function getActivityReporteById(int $id)
    {
        return $this->tableGateway->select(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'nombre',
                'fecha',
                'estado',
                'credito',
                'tipo_participacion',
                'motivo_rechazo',
                'fecha_resolucion'
            ]);

            $select->where(['actividad.id' => $id]);
            $this->applyCommonJoins($select);
        })->current();
    }

    public function buscarActividades(string $buscar)
    {
        return $this->tableGateway->select(function (Select $select) use ($buscar) {
            $this->applyCommonJoins($select);
            $this->applySearchFilter($select, $buscar, 'actividad');
            $select->order(['actividad.estado = 0 DESC']);
        });
    }

    public function getActivityByCredit(int $credito): ?Activity
    {
        return $this->getActivityObject(
            $this->tableGateway->select(['credito' => $credito])->current()
        );
    }

    public function saveActivity(Activity $activity): int
    {
        $data = $activity->getArrayCopy();

        if ($activity->id === null) {
            $this->tableGateway->insert($data);
            return (int)$this->tableGateway->getLastInsertValue();
        }

        $this->tableGateway->update($data, ['id' => $activity->id]);
        return $activity->id;
    }

    public function updateActivity(int $id, int $aprobadoPor): void
    {
        $this->tableGateway->update([
            'estado' => 1,
            'aprobado_por' => $aprobadoPor,
            'fecha_resolucion' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public function rechazoActivity(int $id, string $motivo, int $rechazadoPor): void
    {
        $this->tableGateway->update([
            'estado' => 2,
            'motivo_rechazo' => $motivo,
            'fecha_resolucion' => date('Y-m-d H:i:s'),
            'rechazado_por' => $rechazadoPor,
        ], ['id' => $id]);
    }

    public function fetchAllPorRoles(
        array $tipos,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        if (empty($tipos)) {
            return [];
        }

        $select = new Select($this->tableGateway->getTable());

        $this->applyCommonJoins($select);

        $where = new Where();

        //  Admin ve todo
        if ($tipoId !== 1) {
            $where->in('r.tipo_id', $tipos);
        }

        //  filtro real del usuario
        $this->applyUserFilter(
            $where,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension,
            'r'
        );

        $select->where($where);

        $select->order('actividad.fecha DESC');

        return $this->tableGateway->selectWith($select);
    }

    public function getActividadesPorEstadoYTipos(
        int $estado,
        array $tipos,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension,
        string $buscar = ''
    ) {
        if (empty($tipos)) {
            return [];
        }

        $select = new Select($this->tableGateway->getTable());

        $this->joinOrganizerRole($select);

        $where = new Where();

        $where->equalTo('actividad.estado', $estado);

        // 🔹 filtro base
        $where->in('o.tipo_id', $tipos);

        $this->applyUserFilter(
            $where,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension,
            'o'
        );

        $select->where($where);

        $this->applySearchFilter($select, $buscar, 'actividad');

        $select->order('actividad.fecha DESC');

        return $this->tableGateway->selectWith($select);
    }

    public function getActividadesPorEstado(
        int $estado,
        int $tipoId,
        array $userCarreras,
        int $userExtension,
        string $buscar = ''
    ) {
        $select = new Select($this->tableGateway->getTable());

        $select->columns(['*']);

        $select->join(
            ['r' => 'roles'],
            'r.id = actividad.organizador',
            [],
            Select::JOIN_LEFT
        );

        $where = new Where();

        $where->equalTo('actividad.estado', $estado);

        switch ($tipoId) {

            case 2:
                $where->in('actividad.carrera', $userCarreras);
                $where->equalTo('actividad.extension', $userExtension);
                $where->equalTo('r.tipo_id', 2);
                break;
            case 3: // SECRETARIA

                $where->equalTo('actividad.extension', $userExtension);
                break;

            case 4: // ASOCIACIONES

                if (empty($userCarreras)) {
                    $where->expression('1 = 0');
                    break;
                }

                $where->in('actividad.carrera', $userCarreras);
                $where->equalTo('actividad.extension', $userExtension);
                $where->equalTo('r.tipo_id', 4);
                break;

            default:
                $where->expression('1 = 0');
                break;
        }

        $select->where($where);

        $this->applySearchFilter($select, $buscar, 'actividad');

        $select->order('actividad.fecha DESC');

        return $this->tableGateway->selectWith($select);
    }

    public function getAllActivitys($rol_id = null)
    {
        return $this->tableGateway->select(function (Select $select) use ($rol_id) {
            $this->applyCommonJoins($select);
            if ($rol_id && in_array($rol_id, [2, 5, 6, 7])) {
                $select->where(['usuarios.rol_id' => $rol_id]);
            }
            $select->order(['actividad.estado = 0 DESC']);
        });
    }

    public function searchActivity(
        string $query,
        array $tipos,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $select = new Select($this->tableGateway->getTable());

        $this->joinOrganizerRole($select);

        $where = new Where();

        $where->nest()
            ->like('actividad.nombre', "%$query%")
            ->or
            ->like('o.nombre', "%$query%")
            ->or
            ->equalTo('actividad.credito', is_numeric($query) ? $query : -1)
            ->unnest();

        $where->in('o.tipo_id', $tipos);

        $this->applyUserFilter(
            $where,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension,
            'o'
        );

        $select->where($where);

        return $this->tableGateway->selectWith($select);
    }

    public function editActivity(Activity $activity): int
    {
        $this->tableGateway->update([
            'nombre' => $activity->nombre,
            'organizador' => $activity->organizador,
            'fecha' => $activity->fecha,
            'credito' => $activity->credito,
            'carrera' => $activity->carrera,
        ], ['id' => $activity->id]);

        return $activity->id;
    }

    public function find(int $id): ?Activity
    {
        return $this->getActivityById($id);
    }

    public function getActividadesPorTipos(
        array $tipos,
        int $tipoId,
        array $userCarreras,
        int $userExtension,
        int $rolId
    ) {
        return $this->tableGateway->select(function (Select $select) use (
            $tipos,
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        ) {

            $this->applyCommonJoins($select);

            $where = new Where();

            //  FILTRO POR TIPO
            $where->in('r.tipo_id', $tipos);

            $this->applyUserFilter(
                $where,
                $tipoId,
                $rolId,
                $userCarreras,
                $userExtension,
                'r'
            );

            $select->where($where);

            $select->order([
                'actividad.estado = 0 DESC',
                'actividad.fecha DESC'
            ]);
        });
    }

    public function getActividadesPendientesPorTipos(
        array $tipos,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension
    ) {
        $select = new Select($this->tableGateway->getTable());

        $this->joinOrganizerRole($select);

        $where = new Where();

        $where->equalTo('actividad.estado', 0);

        // 🔹 filtro base (excepto admin)
        if ($tipoId !== 1) {
            $where->in('o.tipo_id', $tipos);
        }

        $this->applyUserFilter(
            $where,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension,
            'o'
        );

        $select->where($where);

        $select->order('actividad.fecha DESC');

        return $this->tableGateway->selectWith($select);
    }

    private function tiposVisiblesPorTipo(
        int $tipoId
    ): array {

        return match ($tipoId) {

            1 => [1, 2, 3, 4],

            2 => [2],

            3 => [2, 3, 4],

            4 => [4],

            default => [],
        };
    }

    //esto es para los dashboards

    private function applyTipoFilter(
        Select $select,
        array $user,
        string $alias = 'a'
    ): void {

        // ADMIN
        if ($user['tipo_id'] === 1) {
            return;
        }

        // JOIN usuarios
        $select->join(
            ['u' => 'usuarios'],
            'u.id = ' . $alias . '.usuario_id',
            [],
            Select::JOIN_INNER
        );

        // JOIN roles
        $select->join(
            ['r' => 'roles'],
            'r.id = u.rol_id',
            [],
            Select::JOIN_INNER
        );

        switch ($user['tipo_id']) {

            case 2: // PERSONAL

                $select->where->equalTo(
                    'r.tipo_id',
                    2
                );

                if (!empty($user['carreras'])) {

                    $select->where->in(
                        $alias . '.carrera',
                        $user['carreras']
                    );
                }

                $select->where->equalTo(
                    $alias . '.extension',
                    $user['extension']
                );

                break;

            case 3: // SECRETARIA

                $select->where->in(
                    'r.tipo_id',
                    [2, 3, 4]
                );

                $select->where->equalTo(
                    $alias . '.extension',
                    $user['extension']
                );

                break;

            case 4: // ASOCIACIÓN

                $select->where->equalTo(
                    'r.tipo_id',
                    4
                );

                if (!empty($user['carreras'])) {

                    $select->where->in(
                        $alias . '.carrera',
                        $user['carreras']
                    );
                }

                $select->where->equalTo(
                    $alias . '.extension',
                    $user['extension']
                );

                break;

            default:

                $select->where->expression('1 = 0');
                break;
        }
    }

    public function getConteoActividadesPorEstado(array $user): array
    {
        [$sql, $select] = $this->createActivitySelect();
        $select->columns([
            'estado',
            'total' => new Expression('COUNT(*)')
        ]);
        $select->group('estado');

        $this->applyTipoFilter($select, $user, 'a');

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        $data = [
            'pendientes' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
        ];

        foreach ($result as $row) {
            match ((int)$row['estado']) {
                0 => $data['pendientes'] = (int)$row['total'],
                1 => $data['aprobadas'] = (int)$row['total'],
                2 => $data['rechazadas'] = (int)$row['total'],
            };
        }

        return $data;
    }

    public function getActividadesPorMes(
        array $user,
        array $filters = []
    ): array {
        // $sql = new Sql($this->tableGateway->getAdapter());

        // $select = $sql->select(['a' => 'actividad']);
        [$sql, $select] = $this->createActivitySelect();
        $select->columns([
            'mes' => new Expression('MONTH(fecha)'),
            'total' => new Expression('COUNT(*)')
        ]);
        $this->applyDashboardFilters(
            $select,
            $filters,
            'a'
        );

        $this->applyTipoFilter($select, $user, 'a');

        $select->group(new Expression('MONTH(fecha)'));
        $select->order('mes');
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        $data = array_fill(1, 12, 0);
        foreach ($result as $row) {
            $data[(int)$row['mes']] = (int)$row['total'];
        }

        return $data;
    }

    public function getAprobadasVsPendientes(array $user, array $filters = []): array
    {
        $sql = new \Laminas\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select(['a' => 'actividad']);
        $select->columns([
            'estado',
            'total' => new Expression('COUNT(*)')
        ]);
        $select->where(['a.estado' => [0, 1, 2]]);
        $select->group('a.estado');

        $this->applyDashboardFilters(
            $select,
            $filters,
            'a'
        );

        $this->applyTipoFilter($select, $user, 'a');

        $select->group('a.estado');

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $data = [
            'pendientes' => 0,
            'aprobadas'  => 0,
            'rechazadas' => 0,
        ];

        foreach ($result as $row) {
            if ((int)$row['estado'] === 0) {
                $data['pendientes'] = (int)$row['total'];
            }
            if ((int)$row['estado'] === 1) {
                $data['aprobadas'] = (int)$row['total'];
            }
            if ((int)$row['estado'] === 2) {
                $data['rechazadas'] = (int)$row['total'];
            }
        }

        return $data;
    }

    public function getTopActividades(array $user, array $filters = []): array
    {
        // $sql = new Sql($this->tableGateway->getAdapter());

        // $select = $sql->select(['a' => 'actividad']);
        [$sql, $select] = $this->createActivitySelect();
        $select->columns([
            'id',
            'nombre',
            'total_estudiantes' =>
            new Expression('COUNT(sa.id)')
        ]);

        $select->join(
            ['al' => 'attendance_list'],
            'al.id_actividad = a.id',
            [],
            Select::JOIN_LEFT
        );

        $select->join(
            ['sa' => 'student_attendance'],
            'sa.id_attendance_list = al.id',
            [],
            Select::JOIN_LEFT
        );
        $this->applyDashboardFilters(
            $select,
            $filters,
            'a'
        );
        $this->applyTipoFilter($select, $user, 'a');

        $select->group(['a.id', 'a.nombre']);
        $select->order('total_estudiantes DESC');
        $select->limit(7); // Limitar a las 10 actividades más populares

        return iterator_to_array(
            $sql->prepareStatementForSqlObject($select)->execute()
        );
    }

    public function getTopUsuarios(
        array $user,
        array $filters = []
    ): array {

        $sql = new Sql(
            $this->tableGateway->getAdapter()
        );

        $select = $sql->select([
            'a' => 'actividad'
        ]);

        $select->columns([
            'total' => new Expression('COUNT(a.id)')
        ]);

        $select->join(
            ['u' => 'usuarios'],
            'u.id = a.usuario_id',
            ['nombre', 'apellido'],
            Select::JOIN_INNER
        );

        $select->join(
            ['r' => 'roles'],
            'r.id = u.rol_id',
            [],
            Select::JOIN_INNER
        );

        $this->applyDashboardFilters(
            $select,
            $filters,
            'a'
        );

        // filtro dinámico por tipo
        if ($user['tipo_id'] !== 1) {

            $tiposVisibles =
                $this->tiposVisiblesPorTipo(
                    $user['tipo_id']
                );

            if (!empty($tiposVisibles)) {

                $select->where->in(
                    'r.tipo_id',
                    $tiposVisibles
                );
            }
        }

        $select->group([
            'u.id',
            'u.nombre',
            'u.apellido'
        ]);

        $select->order('total DESC');

        $select->limit(7);

        return iterator_to_array(
            $sql->prepareStatementForSqlObject(
                $select
            )->execute()
        );
    }

    public function getActividadesPorCarrera(
        array $user,
        array $filters = []
    ): array {

        // $sql = new Sql($this->tableGateway->getAdapter());

        // $select = $sql->select(['a' => 'actividad']);
        [$sql, $select] = $this->createActivitySelect();

        $select->columns([
            'carrera' => 'carrera',
            'total' => new Expression('COUNT(a.id)')
        ]);

        // joins necesarios
        $select->join(
            ['u' => 'usuarios'],
            'u.id = a.usuario_id',
            [],
            Select::JOIN_INNER
        );

        $select->join(
            ['r' => 'roles'],
            'r.id = u.rol_id',
            [],
            Select::JOIN_INNER
        );

        $this->applyDashboardFilters(
            $select,
            $filters,
            'a'
        );

        $this->applyUserFilter(
            $select->where,
            $user['tipo_id'],
            $user['rol_id'],
            $user['carreras'],
            $user['extension'],
            'r',
            'a'
        );

        $select->group('a.carrera');

        return iterator_to_array(
            $sql->prepareStatementForSqlObject($select)
                ->execute()
        );
    }

    public function getTiempoPromedioResolucion(array $user): float
    {
        // $sql = new Sql($this->tableGateway->getAdapter());

        // $select = $sql->select(['a' => 'actividad']);
        [$sql, $select] = $this->createActivitySelect();
        $select->columns([
            'promedio_horas' => new Expression(
                'AVG(TIMESTAMPDIFF(HOUR, fecha, fecha_resolucion))'
            )
        ]);
        $select->where->isNotNull('fecha_resolucion');

        $this->applyTipoFilter($select, $user, 'a');

        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        return round((float)($row['promedio_horas'] ?? 0), 2);
    }

    private function applyUserFilter(
        Where $where,
        int $tipoId,
        int $rolId,
        array $userCarreras,
        int $userExtension,
        string $aliasRol = 'r',
        string $aliasActividad = 'actividad'
    ) {

        switch ($tipoId) {

            case 1: // ADMIN
                break;

            case 2: // PERSONAL

                if (empty($userCarreras)) {
                    $where->expression('1 = 0');
                    return;
                }

                $where->in(
                    "{$aliasActividad}.carrera",
                    $userCarreras
                );

                $where->equalTo(
                    "{$aliasActividad}.extension",
                    $userExtension
                );

                $where->equalTo(
                    "{$aliasRol}.tipo_id",
                    2
                );

                break;

            case 3: // SECRETARIA

                $where->equalTo(
                    "{$aliasActividad}.extension",
                    $userExtension
                );

                break;

            case 4: // ASOCIACION

                if (empty($userCarreras)) {
                    $where->expression('1 = 0');
                    return;
                }

                $where->in(
                    "{$aliasActividad}.carrera",
                    $userCarreras
                );

                $where->equalTo(
                    "{$aliasActividad}.extension",
                    $userExtension
                );

                break;

            default:

                $where->expression('1 = 0');
                break;
        }
    }

    private function applyDashboardFilters(
        Select $select,
        array $filters = [],
        string $alias = 'a'
    ): void {

        $where = $select->where;

        if (!empty($filters['year']) && !is_array($filters['year'])) {
            $where->addPredicate(
                new \Laminas\Db\Sql\Predicate\Expression(
                    "YEAR({$alias}.fecha) = " . (int)$filters['year']
                )
            );
        }

        if (!empty($filters['monthStart']) && !is_array($filters['monthStart'])) {
            $where->addPredicate(
                new \Laminas\Db\Sql\Predicate\Expression(
                    "MONTH({$alias}.fecha) >= " . (int)$filters['monthStart']
                )
            );
        }

        if (!empty($filters['monthEnd']) && !is_array($filters['monthEnd'])) {
            $where->addPredicate(
                new \Laminas\Db\Sql\Predicate\Expression(
                    "MONTH({$alias}.fecha) <= " . (int)$filters['monthEnd']
                )
            );
        }

        if (!empty($filters['dateStart']) && !is_array($filters['dateStart'])) {

            $dateStart = (string)$filters['dateStart'];

            $where->addPredicate(
                new \Laminas\Db\Sql\Predicate\Expression(
                    "{$alias}.fecha >= '{$dateStart}'"
                )
            );
        }

        if (!empty($filters['dateEnd']) && !is_array($filters['dateEnd'])) {

            $dateEnd = (string)$filters['dateEnd'];

            $where->addPredicate(
                new \Laminas\Db\Sql\Predicate\Expression(
                    "{$alias}.fecha <= '{$dateEnd}'"
                )
            );
        }
    }

    private function joinOrganizerRole(
        Select $select,
        string $alias = 'o'
    ): void {
        $select->join(
            [$alias => 'roles'],
            "{$alias}.id = actividad.organizador",
            [
                'organizador_nombre' => 'nombre',
                'tipo_id'
            ],
            Select::JOIN_LEFT
        );
    }

    private function createActivitySelect(
        string $alias = 'a'
    ): array {
        $sql = new Sql($this->tableGateway->getAdapter());
        return [
            $sql,
            $sql->select([$alias => 'actividad'])
        ];
    }
}
