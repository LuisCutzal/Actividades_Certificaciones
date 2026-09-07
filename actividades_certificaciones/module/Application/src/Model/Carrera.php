<?php
namespace Application\Model;

class Carrera
{   
    public ?int $carrera = null;
    public ?string $nombre = null;

    public function __construct(
        ?int $carrera = null,
        ?string $nombre = null,
    ) {
        $this->carrera       = $carrera;
        $this->nombre = $nombre;
    }
     public function exchangeArray(array $data): void
    {
        $this->carrera       = $data['carrera'] ?? null;
        $this->nombre   = $data['nombre'] ?? null;
    }
}
