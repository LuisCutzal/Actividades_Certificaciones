<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;

class AuditLogTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function save(array $data): void
    {
        $this->tableGateway->insert($data);
    }

    public function fetchLogs(?string $entityType = null, ?int $entityId = null, ?string $action = null): array
    {
        $select = $this->tableGateway->getSql()->select();
        if ($entityType) {
            $select->where(['entity_type' => $entityType]);
        }
        if ($entityId) {
            $select->where(['entity_id' => $entityId]);
        }
        if ($action) {
            $select->where(['action' => $action]);
        }
        $select->order('created_at DESC');


        return iterator_to_array($this->tableGateway->selectWith($select));
    }
}