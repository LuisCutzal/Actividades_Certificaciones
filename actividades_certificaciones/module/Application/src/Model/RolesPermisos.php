<?php

namespace Application\Model;

class RolesPermisos
{
    public ?int $rol_id = null;
    public ?int $permiso_id = null;

    public function exchangeArray(array $data): void
    {
        $this->rol_id = $data['rol_id'] ?? null;
        $this->permiso_id = $data['permiso_id'] ?? null;
    }
}
