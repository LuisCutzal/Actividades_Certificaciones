<?php

namespace Application\Model;

class Student
{
    public ?int $carnet = null;
    public ?string $nombre = null;
    public ?string $dpi = null;
    public ?int $extension = null;
    public ?string $usuario = null;
    public function exchangeArray(array $data): void
    {
        $this->carnet = $data['carnet'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->extension = $data['extension'] ?? null;
        $this->usuario = $data['usuario'] ?? null;
        $this->dpi = $data['dpi'] ?? null;
    }
}
