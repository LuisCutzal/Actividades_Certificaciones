<?php

namespace Application\Service;

use Application\Model\AuditLogTable;

class AuditService
{
    private AuditLogTable $auditLogTable;

    public function __construct(AuditLogTable $auditLogTable)
    {
        $this->auditLogTable = $auditLogTable;
    }

    public function log(
        ?int $userId,
        ?string $user,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $details = null
    ): void {

        $this->auditLogTable->save([
            'user_id' => $userId,
            'user' => $user,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'details' => $details
                ? json_encode($details)
                : null,
        ]);
    }

    public function getLogs(
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $action = null
    ): array {
        return $this->auditLogTable->fetchLogs($entityType, $entityId, $action);
    }
}