<?php
// clase encargada de realizar consultas a la tabla usuarios en la base de datos
namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Sql;

class RolTable
{
    private TableGatewayInterface $tableGateway;
    //recibe el TableGateway configurado en la fábrica.
    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getRol()
    {
        return $this->tableGateway->select(function ($select) {
            $select->order('id ASC');
        });
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function crearRol(array $data): int
    {
        $this->tableGateway->insert($data);
        return (int) $this->tableGateway->getLastInsertValue();
    }

    public function buscarRol(string $query): array
    {
        $resultSet = $this->tableGateway->select(function ($select) use ($query) {
            $select->where->like('nombre', '%' . $query . '%');
        });

        $roles = [];
        foreach ($resultSet as $row) {
            $roles[] = $row;
            // var_dump($roles);
            // exit;
        }

        return $roles;
    }

    public function getRolById(int $id)
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        return $rowset->current();
    }

    public function updateRol(int $id, array $data)
    {
        return $this->tableGateway->update($data, ['id' => $id]);
    }

    public function getRolConTipo(int $rolId, TipoTable $tipoTable): ?array
    {
        $rolRow = $this->tableGateway->select(['id' => $rolId])->current();
        if (!$rolRow) return null;

        $tipo = null;
        if (!empty($rolRow->tipo_id)) {
            $tipo = $tipoTable->getTipoById((int)$rolRow->tipo_id);
        }

        return [
            'id' => $rolRow->id,
            'nombre' => $rolRow->nombre,
            'tipo_id' => $rolRow->tipo_id,
            'tipo_nombre' => $tipo->nombre ?? null,
            //'sidebar' => $tipo->nombre ?? 'default'
        ];
    }

    public function getTipoIdByRolId(int $rolId): ?int
    {
        $row = $this->tableGateway->select(['id' => $rolId])->current();

        return $row ? (int)$row->tipo_id : null;
    }


    // public function getCarreraIdByRolId(int $rolId): ?int
    // {
    //     $sql = new Sql($this->tableGateway->getAdapter());

    //     $select = $sql->select();
    //     $select->from('rol_carrera');
    //     $select->columns(['carrera_id']);
    //     $select->where(['rol_id' => $rolId]);
    //     $select->limit(1);

    //     $statement = $sql->prepareStatementForSqlObject($select);
    //     $result = $statement->execute()->current();

    //     return $result
    //         ? (int)$result['carrera_id']
    //         : null;
    // }
    
    // public function getCarreraIdByRolId(int $rolId): ?int
    // {
    //     $resultSet = $this->tableGateway->select([
    //         'rol_id' => $rolId
    //     ]);
    //     $row = $resultSet->current();
    //     return $row
    //         ? (int) $row->carrera_id
    //         : null;
    // }

    public function getExtensionByRolId(int $rolId): ?int
    {
        $row = $this->tableGateway
            ->select(['id' => $rolId])
            ->current();

        return $row
            ? (int)$row->extension
            : null;
    }
}
