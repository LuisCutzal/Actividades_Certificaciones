<?php

namespace Application\Model;

class Permisos
{
    public ?int $id = null;
    public ?string $nombre = null;
    public ?string $descripcion = null;

    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->descripcion = $data['descripcion'] ?? null;
    }
}
