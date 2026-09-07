<?php

namespace Application\Model;

use Application\Model\Inscripcion;
use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Select;


class InscripcionTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getById(int $id): ?Inscripcion
    {
        $row = $this->tableGateway->select(['inscripcion' => $id])->current();

        if (!$row) {
            return null;
        }

        $lista = new Inscripcion();
        $lista->exchangeArray((array) $row);

        return $lista;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function estudianteInscrito(int $carnet, int $carrera, int $extension): bool
    {
        $anioActual = date('Y');

        $rowset = $this->tableGateway->select([
            'carnet' => $carnet,
            'carrera' => $carrera,
            'extension' => $extension,
            'anio' => $anioActual
        ]);

        return $rowset->current() ? true : false;
    }

    public function obtenerCarreraActual(int $carnet)
    {
        $select = new Select('inscripcion');

        $select->columns(['carrera', 'extension'])
            ->where(['carnet' => $carnet])
            ->order(['anio DESC', 'semestre DESC']);
            //->limit(1);

        return $this->tableGateway
            ->selectWith($select);
            //->current();
    }
}
