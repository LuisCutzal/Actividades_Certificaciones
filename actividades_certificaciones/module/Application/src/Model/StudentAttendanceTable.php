<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;

class StudentAttendanceTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function insert(array $data): void
    {
        $this->tableGateway->insert($data);
    }

    public function exists(int $studentId, int $activityId): bool
    {
        $row = $this->tableGateway
            ->select(['carnet' => $studentId, 'id_activity' => $activityId])
            ->current();

        return $row !== null;
    }

    public function delete(array $where)
    {
        return $this->tableGateway->delete($where);
    }

}
