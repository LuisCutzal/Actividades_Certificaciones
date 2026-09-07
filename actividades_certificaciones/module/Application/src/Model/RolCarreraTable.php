<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;


class RolCarreraTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }


    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    //obtiene todas las carreras asociadas a un rol específico.
    public function getCarrerasByRol(int $rolId): array
    {
        // Obtiene todos los registros donde el rol_id  coincida con el rol recibido
        $resultSet = $this->tableGateway->select([
            'rol_id' => $rolId
        ]);

        // Arreglo donde se almacenarán únicamente los IDs de las carreras encontradas
        $carreras = [];

        // Recorre cada fila obtenida de la consulta
        foreach ($resultSet as $row) {

            // Agrega el carrera_id al arreglo  convirtiéndolo a entero
            $carreras[] = (int) $row->carrera_id;
        }

        // Retorna el arreglo con los IDs de carreras asignadas al rol
        return $carreras;
    }

    public function insert(array $data): int
    {
        $this->tableGateway->insert($data);
        return (int) $this->tableGateway->getLastInsertValue();
    }

    public function deleteByRol(int $rolId)
    {
        return $this->tableGateway->delete([
            'rol_id' => $rolId
        ]);
    }

    public function getCarreraIdByRolId(int $rolId): ?int
    {
        $row = $this->tableGateway
            ->select([
                'rol_id' => $rolId
            ])
            ->current();

        return $row ? (int)$row->carrera_id : null;
    }
}
