<?php

namespace Application\Model;

class ReportePdf
{
    public ?int $id = null;
    public ?int $tipo = null;
    public ?int $carnet = null;
    public ?int $id_actividad = null;
    public ?int $generado_por = null;
    public ?int $rol_generador = null;
    public ?int $estado_actividad = null;
    public ?string $correlativo = null;
    public ?string $fecha_generado = null;

    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->tipo = $data['tipo'] ?? null;
        $this->carnet = $data['carnet'] ?? null;
        $this->id_actividad = $data['id_actividad'] ?? null;
        $this->generado_por = $data['generado_por'] ?? null;
        $this->rol_generador = $data['rol_generador'] ?? null;
        $this->estado_actividad = $data['estado_actividad'] ?? null;
        $this->correlativo = $data['correlativo'] ?? null;
        $this->fecha_generado = $data['fecha_generado'] ?? null;
    }
}
