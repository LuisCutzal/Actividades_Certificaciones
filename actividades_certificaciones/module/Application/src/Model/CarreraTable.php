<?php
// clase encargada de realizar consultas a la tabla usuarios en la base de datos
namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use RuntimeException;

class CarreraTable
{
    private TableGatewayInterface $tableGateway;
    //recibe el TableGateway configurado en la fábrica.
    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function getCarrera(int $id): Carrera
    {
        $rowset = $this->tableGateway->select(['carrera' => $id]);
        $row = $rowset->current();

        if (!$row) {
            throw new RuntimeException("Carrera no encontrada");
        }

        return $row;
    }

    public function fetchByIds(array $ids)
    {
        return $this->tableGateway->select(function ($select) use ($ids) {
            $select->where->in('carrera', $ids);
        });
    }

    public function getCarrerasMap(): array
    {
        $result = $this->tableGateway->select();

        $map = [];

        foreach ($result as $row) {
            $map[$row->carrera] = $row->nombre;
        }

        return $map;
    }
}
