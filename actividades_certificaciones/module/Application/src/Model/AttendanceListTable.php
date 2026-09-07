<?php

namespace Application\Model;

use Application\Model\AttendanceList;
use Laminas\Db\TableGateway\TableGatewayInterface;


class AttendanceListTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function insert(array $data): int
    {
        $this->tableGateway->insert($data);
        return $this->tableGateway->getLastInsertValue();
    }

    public function getById(int $id): ?AttendanceList
    {
        $row = $this->tableGateway->select(['id' => $id])->current();

        if (!$row) {
            return null;
        }

        $lista = new AttendanceList();
        $lista->exchangeArray((array) $row);

        return $lista;
    }

    public function updateStatus(int $id, int $estado): void
    {
        $this->tableGateway->update(['estado' => $estado], ['id' => $id]);
    }

    public function getByActivity(int $idActividad): array
    {
        return $this->tableGateway
            ->select(['id_actividad' => $idActividad])
            ->toArray();
    }

    public function update(array $data, array $where): void
    {
        $this->tableGateway->update($data, $where);
    }

    public function delete(array $where)
    {
        return $this->tableGateway->delete($where);
    }


    public function find(int $id): ?AttendanceList
    {
        return $this->getById($id);
    }

    public function fetchByActivity(int $actividadId): ?AttendanceList
    {
        $row = $this->tableGateway->select(['id_actividad' => $actividadId])->current();

        if (!$row) {
            return null;
        }
        $lista = new AttendanceList();
        $lista->exchangeArray((array)$row);

        return $lista;
    }

    public function fetchByActividad(int $actividadId): ?array
    {
        $rowset = $this->tableGateway->select(['id_actividad' => $actividadId]);
        $row = $rowset->current();
        return $row ? (array) $row : null;
    }

    public function rejectByActividad(int $idActividad): void
    {
        $this->tableGateway->update(
            [
                'estado' => 2,
                'rechazado' => date('Y-m-d H:i:s'),
            ],
            [
                'id_actividad' => $idActividad
            ]
        );
    }
}
