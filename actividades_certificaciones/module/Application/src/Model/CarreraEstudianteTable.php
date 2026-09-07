<?php
// clase encargada de realizar consultas a la tabla usuarios en la base de datos
namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Where;

class CarreraEstudianteTable
{
    private TableGatewayInterface $tableGateway;
    //recibe el TableGateway configurado en la fábrica.
    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getCarrerasPorEstudiante(int $carnet): array
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->columns(['carrera']);

        $select->where([
            'carnet' => $carnet
        ]);

        $result = $this->tableGateway->selectWith($select);

        $carreras = [];

        foreach ($result as $row) {

            $carreras[] = (int)$row->carrera;
        }

        return $carreras;
    }

    public function getCarnetsPorCarrera(array $carreras): array
    {
        if (empty($carreras)) {
            return [];
        }

        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->where(function (Where $where) use ($carreras) {

            $where->in('carrera', $carreras);
        });

        $result = $this->tableGateway->selectWith($select);

        $carnets = [];

        foreach ($result as $row) {
            $carnets[(int)$row->carnet] = (int)$row->carnet;
        }

        return array_values($carnets);
    }
}
