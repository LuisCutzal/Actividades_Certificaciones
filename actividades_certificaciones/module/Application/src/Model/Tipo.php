<?php
//representa a un usuario del sistema como un objeto
namespace Application\Model;
/**
 * Modelo que representa un usuario del sistema.
 *
 * Esta clase actúa como el "contenedor" de los datos del usuario y se utiliza
 * tanto para cargar información desde la base de datos (con exchangeArray),
 * como para convertir el objeto nuevamente en array (getArrayCopy).
 */
class Tipo
{   // Propiedades públicas del usuario. 
    public ?int $id = null;
    public ?string $nombre = null;
    public ?string $descripcion = null;

    // constructor para inicializar el objeto Use
    public function __construct(
        ?int $id = null,
        ?string $nombre = null,
        ?string $descripcion = null
    ) {
        $this->id       = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }
    //Carga los valores del usuario desde un arreglo.
    // Este método lo usa automáticamente el ResultSet del TableGateway.
    public function exchangeArray(array $data): void
    {
        $this->id       = $data['id'] ?? null;
        $this->nombre   = $data['nombre'] ?? null;
        $this->descripcion  = $data['descripcion'] ?? null;
    }
    //Convierte el objeto User a un arreglo asociativo
    public function getArrayCopy(): array
    {
        return get_object_vars($this);
    }
}
