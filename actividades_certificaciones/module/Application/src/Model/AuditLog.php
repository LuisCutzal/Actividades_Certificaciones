<?php

namespace Application\Model;

class AuditLog
{
    public ?int $id = null;

    public ?int $user_id = null;
    public ?string $user = null;

    public ?string $action = null;

    public ?string $entity_type = null;
    public ?int $entity_id = null;

    public ?string $description = null;

    public ?string $details = null;

    public ?string $created_at = null;

    public function exchangeArray(array $data): void
    {
        $this->id = isset($data['id'])
            ? (int)$data['id']
            : null;

        $this->user_id = isset($data['user_id'])
            ? (int)$data['user_id']
            : null;

        $this->user = $data['user'] ?? null;

        $this->action = $data['action'] ?? null;

        $this->entity_type = $data['entity_type'] ?? null;

        $this->entity_id = isset($data['entity_id'])
            ? (int)$data['entity_id']
            : null;

        $this->description = $data['description'] ?? null;

        $this->details = $data['details'] ?? null;

        $this->created_at = $data['created_at'] ?? null;
    }
}