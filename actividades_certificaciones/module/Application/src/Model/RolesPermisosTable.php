<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;


class RolesPermisosTable
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

    public function asignarPermiso(array $data): void
    {
        $this->tableGateway->insert($data);
    }

    public function deleteByRol(int $rolId)
    {
        return $this->tableGateway->delete(['rol_id' => $rolId]);
    }

    public function getPermisosByRolId(int $rolId): array
    {
        $resultSet = $this->tableGateway->select([
            'rol_id' => $rolId
        ]);

        $permisos = [];

        foreach ($resultSet as $row) {
            $permisos[] = (int) $row->permiso_id;
        }

        return $permisos;
    }

    public function insert(array $data)
    {
        return $this->tableGateway->insert($data);
    }
}
