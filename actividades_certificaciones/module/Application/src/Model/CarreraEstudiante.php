<?php

namespace Application\Model;

class CarreraEstudiante
{
    public ?int $carnet = null;
    public ?int $carrera = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_cierre = null;
    public ?string $status_estudiante = null;

    public function __construct(
        ?int $carnet = null,
        ?int $carrera = null,
        ?string $fecha_inicio = null,
        ?string $fecha_cierre = null,
        ?string $status_estudiante = null,
    ) {
        $this->carnet       = $carnet;
        $this->carrera       = $carrera;
        $this->fecha_inicio       = $fecha_inicio;
        $this->fecha_cierre       = $fecha_cierre;
        $this->status_estudiante       = $status_estudiante;
    }
    public function exchangeArray(array $data): void
    {
        $this->carnet       = $data['carnet'] ?? null;
        $this->carrera       = $data['carrera'] ?? null;
        $this->fecha_inicio   = $data['fecha_inicio'] ?? null;
        $this->fecha_cierre       = $data['fecha_cierre'] ?? null;

        $this->status_estudiante       = $data['status_estudiante'] ?? null;
    }
}
