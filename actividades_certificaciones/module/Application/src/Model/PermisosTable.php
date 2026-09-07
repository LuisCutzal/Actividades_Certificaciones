<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;

class PermisosTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getPermisosByRol(int $rolId): array
    {
        $select = $this->tableGateway->getSql()->select();

        $select->quantifier('DISTINCT');
        $select->columns(['nombre']);

        $select->join(
            ['rp' => 'roles_permisos'],
            'rp.permiso_id = permisos.id',
            []
        );

        $select->where(['rp.rol_id' => $rolId]);

        $result = $this->tableGateway->selectWith($select);

        $permisos = [];

        foreach ($result as $row) {
            $permisos[] = $row['nombre'];
        }

        return $permisos;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }
}
