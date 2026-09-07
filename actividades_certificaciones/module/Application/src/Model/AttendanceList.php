<?php

namespace Application\Model;

class AttendanceList
{
    public ?int $id = null;
    public ?int $id_actividad = null;
    public ?int $id_usuario= null;
    public ?int $estado= null;
    public ?string $creado= null;
    public ?string $aprobado = null;
    public ?string $rechazado = null;

    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->id_actividad = $data['id_actividad']?? null;
        $this->id_usuario = $data['id_usuario']?? null;
        $this->estado = $data['estado']?? null;
        $this->creado = $data['creado']?? null;
        $this->aprobado = $data['aprobado'] ?? null;
        $this->rechazado = $data['rechazado'] ?? null;
    }
}
