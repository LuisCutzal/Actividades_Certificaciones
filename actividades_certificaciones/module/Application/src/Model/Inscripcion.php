<?php

namespace Application\Model;

class Inscripcion
{
    public ?int $anio = null;
    public ?int $semestre = null;
    public ?int $extension = null;
    public ?int $carnet = null;
    public ?int $carrera = null;
    public ?string $fecha_inscripcion = null;
    public ?string $fecha_registro = null;

    public function exchangeArray(array $data): void
    {
        $this->anio = $data['anio'] ?? null;
        $this->semestre = $data['semestre'] ?? null;
        $this->extension = $data['extension'] ?? null;
        $this->carnet = $data['carnet'] ?? null;
        $this->carrera = $data['carrera'] ?? null;
        $this->fecha_inscripcion = $data['fecha_inscripcion'] ?? null;
        $this->fecha_registro = $data['fecha_registro'] ?? null;
    }
}
