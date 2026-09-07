<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use \Laminas\Db\Sql\Expression;

class StudentTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getByCarnet(int $carnet): ?Student
    {
        $row = $this->tableGateway->select(['carnet' => $carnet])->current();

        if (!$row) {
            return null;
        }

        $student = new Student();
        $student->exchangeArray((array) $row);

        return $student;
    }

    public function insert(array $data): int
    {
        $this->tableGateway->insert($data);
        return $this->tableGateway->getLastInsertValue();
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function buscarPorNombreOCarnet(
        string $q,
        int $extension
    ) {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $this->joinCarreraEstudiante($select);
        $this->joinCarrera($select);
        $this->joinInscripcionActual($select);

        // búsqueda
        $select->where
            ->nest
            ->like('estudiante.nombre', "%$q%")
            ->or
            ->equalTo('estudiante.carnet', $q)
            ->unnest
            ->equalTo('estudiante.extension', $extension);

        return $this->tableGateway->selectWith($select);
    }

    //esto es para ver a un estudiante dependiendo de la carrera

    public function fetchByCarnets(array $carnets, int $extension, array $carrerasUsuario)
    {
        if (empty($carnets)) {
            return [];
        }

        //$anioActual = date('Y');

        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $this->joinCarreraEstudiante($select);

        $this->joinCarrera($select);

        $this->joinInscripcionActual($select);

        $select->where
            ->in('estudiante.carnet', $carnets)
            ->equalTo('estudiante.extension', $extension)
            ->in('carrera_estudiante.carrera', $carrerasUsuario);

        return $this->tableGateway->selectWith($select);
    }

    public function fetchByExtension(int $extension)
    {
        //$anioActual = date('Y');

        $select = $this->tableGateway
            ->getSql()
            ->select();

        // JOIN carrera_estudiante

        $this->joinCarreraEstudiante(
            $select,
            [
                'status_estudiante',
                'carrera'
            ]
        );

        $this->joinCarrera($select);

        $this->joinInscripcionActual($select);

        $select->where
            ->equalTo('estudiante.extension', $extension);

        return $this->tableGateway
            ->selectWith($select);
    }


    public function buscarPorNombreOCarnetYCarnets(
        string $q,
        array $carnets,
        array $carreras,
        int $extension
    ) {
        if (empty($carnets)) {
            return [];
        }

        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $this->joinCarreraEstudiante($select);

        $this->joinCarrera($select);

        // filtros base
        $select->where
            ->in('estudiante.carnet', $carnets)
            ->in('carrera_estudiante.carrera', $carreras)
            ->equalTo('estudiante.extension', $extension);

        // búsqueda
        $select->where
            ->nest
            ->like('estudiante.nombre', "%$q%")
            ->or
            ->equalTo('estudiante.carnet', $q)
            ->unnest;

        return $this->tableGateway->selectWith($select);
    }

    public function existePorNombreOCarnet(string $q): bool
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['carnet']);
        $select->where
            ->like('nombre', "%$q%")
            ->or
            ->equalTo('carnet', $q);

        return (bool) $this->tableGateway->selectWith($select)->current();
    }

    public function getPorCarnets(
        array $carnets
    ): array {

        if (empty($carnets)) {
            return [];
        }

        $result = $this->tableGateway->select(
            function (Select $select)
            use ($carnets) {

                $select->columns([
                    'carnet',
                    'nombre'
                ]);

                $select->where->in(
                    'carnet',
                    $carnets
                );

            }
        );

        return iterator_to_array($result);
    }

    private function getAnioActual(): int
    {
        return (int) date('Y');
    }


    public function estudianteValidoAsociacion(
        int $carnet,
        int $carrera,
        int $extension
    ): bool {

        $anioActual =  $this->getAnioActual();

        $adapter = $this->tableGateway->getAdapter();
        $sql = new Sql($adapter);

        $select = $sql->select();

        $select->from(['ce' => 'carrera_estudiante']);

        // JOIN estudiante
        $select->join(
            ['e' => 'estudiante'],
            'e.carnet = ce.carnet',
            [],
            Select::JOIN_INNER
        );

        // JOIN inscripcion
        $select->join(
            ['i' => 'inscripcion'],
            'i.carnet = ce.carnet AND i.carrera = ce.carrera',
            [],
            Select::JOIN_INNER
        );

        $select->columns(['carnet']);

        $select->where([
            'ce.carnet' => $carnet,
            'ce.carrera' => $carrera,
            'i.extension' => $extension,
            'i.anio' => $anioActual,
        ]);

        // estados invalidos
        $select->where
            ->isNull('ce.fecha_cierre');

        $select->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute()->current();

        return $result ? true : false;
    }

    private function joinCarreraEstudiante(
        Select $select,
        array $columns = [
            'carrera',
            'fecha_cierre'
        ]
    ): void {

        $select->join(
            'carrera_estudiante',
            'estudiante.carnet = carrera_estudiante.carnet',
            $columns,
            $select::JOIN_LEFT
        );
    }

    private function joinCarrera(
        Select $select
    ): void {

        $select->join(
            'carrera',
            'carrera_estudiante.carrera = carrera.carrera',
            [
                'nombre_carrera' => 'nombre'
            ],
            $select::JOIN_LEFT
        );
    }

    private function joinInscripcionActual(
        Select $select
    ): void {

        $anioActual = date('Y');

        $select->join(
            'inscripcion',
            new Expression(
                'estudiante.carnet = inscripcion.carnet
            AND carrera_estudiante.carrera = inscripcion.carrera
            AND inscripcion.anio = ?',
                [$anioActual]
            ),
            [
                'anio_inscrito' => 'anio'
            ],
            $select::JOIN_LEFT
        );
    }
}
