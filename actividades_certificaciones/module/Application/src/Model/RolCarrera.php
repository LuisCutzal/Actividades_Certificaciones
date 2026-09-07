<?php

namespace Application\Model;

class RolCarrera
{
    public ?int $rol_id = null;
    public ?int $carrera_id = null;

    public function exchangeArray(array $data): void
    {
        $this->rol_id = $data['rol_id'] ?? null;
        $this->carrera_id = $data['carrera_id'] ?? null;
    }
}
