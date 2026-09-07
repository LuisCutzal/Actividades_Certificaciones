<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;

class ReportePdfTable
{
    private TableGatewayInterface $tableGateway;
    private string $schema;

    public function __construct(TableGatewayInterface $tableGateway, string $schema)
    {
        $this->tableGateway = $tableGateway;
        $this->schema = $schema;
    }

    public function insert(array $data): int
    {
        $this->tableGateway->insert($data);
        return (int) $this->tableGateway->getLastInsertValue();
    }

    public function buscarPorCorrelativo(?string $q = null): array
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        // Columnas que queremos
        $select->columns([
            'id',
            'tipo',
            'correlativo',
            'fecha_generado',
            'carnet',
            'id_actividad',
        ]);

        $this->joinEstudiante($select);

        $this->joinActividad($select);

        $this->joinUsuarioGenerador($select);

        $this->joinRolGenerador($select);

        // Filtro por correlativo
        if ($q) {
            $select->where->like('reporte_pdf.correlativo', "%$q%");
        }

        // Orden
        $select->order('reporte_pdf.fecha_generado DESC');

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }



    public function buscarConstanciasPorCorrelativo(
        string $correlativo,
        int $carnetEstudiante
    ) {

        $sql = new Sql($this->tableGateway->getAdapter());

        $select = $sql->select();

        $select->from(['rp' => 'reporte_pdf']);

        // Columnas principales
        $select->columns([
            'id',
            'tipo',
            'correlativo',
            'fecha_generado',
            'carnet',
        ]);

        $this->joinUsuarioGenerador(
            $select,
            'rp',
            'usuario_generador'
        );

        $select->where->equalTo(
            'rp.carnet',
            $carnetEstudiante
        );

        if (!empty($correlativo)) {

            $select->where->like(
                'rp.correlativo',
                '%' . $correlativo . '%'
            );
        }

        // 🔹 Orden
        $select->order('rp.fecha_generado DESC');

        return $this->tableGateway
            ->selectWith($select);
    }

    public function obtenerPorCorrelativo(string $correlativo)
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->columns([
            'id',
            'tipo',
            'correlativo',
            'fecha_generado',
            'carnet',
            'id_actividad',
        ]);

        $select->where(['reporte_pdf.correlativo' => $correlativo]);

        return $this->tableGateway->selectWith($select)->current();
    }

    public function obtenerConstanciasPorEstudiante(int $idEstudiante): array
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->columns([
            'correlativo',
            'tipo',
            'fecha_generado',
        ]);

        $this->joinUsuarioGenerador(
            $select,
            'reporte_pdf',
            'usuario_generador'
        );

        $select->where([
            'reporte_pdf.carnet' => $idEstudiante,
        ]);

        // Solo constancias por estudiante
        $select->where->like('reporte_pdf.correlativo', 'EST-%');

        $select->order('fecha_generado DESC');

        return iterator_to_array(
            $this->tableGateway->selectWith($select)
        );
    }

    public function generarCorrelativo(string $prefijo, int $anio): string
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->columns(['correlativo']);
        $select->where->like('correlativo', "$prefijo-$anio-%");
        $select->order('id DESC');
        $select->limit(1);

        $row = $this->tableGateway->selectWith($select)->current();

        if ($row) {
            //$ultimoNumero = (int) substr($row['correlativo'], -4);
            $ultimoNumero = (int) substr(
                $row['correlativo'],
                strrpos($row['correlativo'], '-') + 1
            );
            $siguiente = $ultimoNumero + 1;
        } else {
            $siguiente = 1;
        }

        return sprintf('%s-%d-%04d', $prefijo, $anio, $siguiente);
    }

    private function joinEstudiante(
        Select $select,
        string $tablaAlias = 'reporte_pdf',
        string $aliasNombre = 'estudiante_nombre'
    ): void {

        $select->join(
            ['e' => new Expression($this->schema . '.estudiante')],
            "e.carnet = {$tablaAlias}.carnet",
            [
                $aliasNombre => 'nombre'
            ],
            $select::JOIN_LEFT
        );
    }

    private function joinActividad(
        Select $select,
        string $tablaAlias = 'reporte_pdf'
    ): void {

        $select->join(
            ['a' => 'actividad'],
            "a.id = {$tablaAlias}.id_actividad",
            [
                'actividad_nombre' => 'nombre'
            ],
            $select::JOIN_LEFT
        );
    }

    private function joinUsuarioGenerador(
        Select $select,
        string $tablaAlias = 'reporte_pdf',
        string $aliasNombre = 'generado_por_nombre'
    ): void {

        $select->join(
            ['u' => 'usuarios'],
            "u.id = {$tablaAlias}.generado_por",
            [
                $aliasNombre => new Expression(
                    "CONCAT(u.nombre, ' ', u.apellido)"
                )
            ],
            $select::JOIN_LEFT
        );
    }
    private function joinRolGenerador(
        Select $select,
        string $tablaAlias = 'reporte_pdf'
    ): void {

        $select->join(
            ['r' => 'roles'],
            "r.id = {$tablaAlias}.rol_generador",
            [
                'rol_generador_nombre' => 'nombre'
            ],
            $select::JOIN_LEFT
        );
    }
}
