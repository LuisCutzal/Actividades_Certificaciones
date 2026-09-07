<?php
//representa a un usuario del sistema como un objeto
namespace Application\Model;

/**
 * Modelo que representa una actividad del sistema.
 *
 * Esta clase actúa como el "contenedor" de los datos de las clases y se utiliza
 * tanto para cargar información desde la base de datos (con exchangeArray),
 * como para convertir el objeto nuevamente en array (getArrayCopy).
 */
class Activity
{   // Propiedades públicas del usuario. 
    public ?int $id = null;
    public ?string $nombre = null;
    public ?int $organizador = null;
    public ?string $fecha = null;
    public ?int $usuario_id = null;
    public ?int $estado = null; //estado sera porque estara por aprobarse, la secretaria cambiara el estado a 1 que dira aprobado
    public ?float $credito = null;
    public ?int $carrera = null;
    public ?string $tipo_participacion = null;
    public ?string $motivo_rechazo = null;
    public ?string $fecha_resolucion = null;
    public ?string $organizador_nombre = null;
    public ?int $carrera_id = null;
    public ?string $carrera_nombre = null;
    public ?int $extension = null;
    public ?int $aprobado_por = null;
    public ?int $rechazado_por = null;

    // constructor para inicializar el objeto Use
    public function __construct(
        ?int $id = null,
        ?string $nombre = null,
        ?string $organizador = null,
        ?string $fecha = null,
        ?int $usuario_id = null,
        ?int $estado = null,
        ?float $credito = null,
        ?string $carrera = null,
        ?string $tipo_participacion = null,
        ?string $motivo_rechazo = null,
        ?string $fecha_resolucion = null,
        ?string $organizador_nombre = null,
        ?int $extension = null,
        ?int $aprobado_por = null,
        ?int $rechazado_por = null,
    ) {
        $this->id       = $id;
        $this->nombre = $nombre;
        $this->organizador = $organizador;
        $this->fecha      = $fecha;
        $this->usuario_id      = $usuario_id;
        $this->estado   = $estado;
        $this->credito   = $credito;
        $this->carrera   = $carrera;
        $this->tipo_participacion   = $tipo_participacion;
        $this->motivo_rechazo   = $motivo_rechazo;
        $this->fecha_resolucion   = $fecha_resolucion;
        $this->organizador_nombre   = $organizador_nombre;
        $this->extension   = $extension;
        $this->aprobado_por   = $aprobado_por;
        $this->rechazado_por   = $rechazado_por;
    }
    //Carga los valores de la activdad desde un arreglo.
    // Este método lo usa automáticamente el ResultSet del TableGateway.
    public function exchangeArray(array $data): void
    {
        $this->id       = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->organizador = $data['organizador'] ?? null;
        $this->fecha      = $data['fecha'] ?? null;
        $this->usuario_id      = $data['usuario_id'] ?? null;
        $this->estado   = isset($data['estado']) ? (int)$data['estado'] : 0; //el estado debe de ser 0 ya que si es 1 automaticamente creara y aprobara la actividad aun si no es secretaria academica
        $this->credito      = $data['credito'] ?? null;

        $this->carrera_id = isset($data['carrera_id']) ? (int)$data['carrera_id'] : null;
        $this->carrera = $data['carrera'] ?? null;

        $this->tipo_participacion   = $data['tipo_participacion'] ?? null;
        $this->motivo_rechazo   = $data['motivo_rechazo'] ?? null;
        $this->fecha_resolucion   = $data['fecha_resolucion'] ?? null;
        //$this->organizador_nombre   = $data['organizador_nombre'] ?? null;
        if (isset($data['organizador_nombre'])) {
            $this->organizador_nombre = $data['organizador_nombre'];
        }
        if (isset($data['carrera_nombre'])) {
            $this->carrera_nombre = $data['carrera_nombre'];
        }
        $this->extension   = $data['extension'] ?? null;

        $this->aprobado_por = isset($data['aprobado_por'])
            ? (int)$data['aprobado_por']
            : null;

        $this->rechazado_por = isset($data['rechazado_por'])
            ? (int)$data['rechazado_por']
            : null;
    }
    //Convierte el objeto Activity a un arreglo asociativo
    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'organizador' => $this->organizador,
            'fecha' => $this->fecha,
            'usuario_id' => $this->usuario_id,
            'estado' => $this->estado,
            'credito' => $this->credito,
            'carrera' => $this->carrera,
            'tipo_participacion' => $this->tipo_participacion,
            'motivo_rechazo' => $this->motivo_rechazo,
            'fecha_resolucion' => $this->fecha_resolucion,
            'extension' => $this->extension,
            'aprobado_por' => $this->aprobado_por,
            'rechazado_por' => $this->rechazado_por,

            //'organizador_nombre' => $this->organizador_nombre
        ];
    }
}
