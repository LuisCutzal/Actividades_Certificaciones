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
class User
{   // Propiedades públicas del usuario. 
    public ?int $id = null;
    public ?string $nombre = null;
    public ?string $apellido = null;
    public ?string $password = null;
    public ?int $estado = 1;
    public ?string $correo = null;
    //public ?int $codigo_Carrera = null;
    public ?int $rol_id = null;
    public ?string $rol_nombre = null;
    public ?int $carnet = null;
    public ?int $cui = null;
    public ?int $registro_personal = null;
    public ?int $extension = null;
    //public ?int $tipo_id = null;


    // constructor para inicializar el objeto Use
    public function __construct(
        ?int $id = null,
        ?string $nombre = null,
        ?string $apellido = null,
        ?string $password = null,
        ?int $estado = null,
        ?string $correo = null,
        //?int $codigo_Carrera = null,
        ?int $rol_id = null,
        ?int $carnet = null,
        ?int $cui = null,
        ?int $registro_personal = null,
        ?int $extension = null,
        //?int $tipo_id = null

    ) {
        $this->id       = $id;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->password = $password;
        $this->estado   = $estado;
        $this->correo   = $correo;
        //$this->codigo_Carrera   = $codigo_Carrera;
        $this->rol_id      = $rol_id;
        $this->carnet      = $carnet;
        $this->cui      = $cui;
        $this->registro_personal      = $registro_personal;
        $this->extension      = $extension;
        //$this->tipo_id      = $tipo_id;
    }
    //Carga los valores del usuario desde un arreglo.
    // Este método lo usa automáticamente el ResultSet del TableGateway.
    public function exchangeArray(array $data): void
    {
        $this->id       = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->apellido = $data['apellido'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->estado   = $data['estado'] ?? 1;
        $this->correo   = $data['correo'] ?? null;
        //$this->codigo_Carrera   = $data['codigo_Carrera'] ?? null;
        $this->rol_id      = $data['rol_id'] ?? null;
        if (isset($data['rol_nombre'])) {
            $this->rol_nombre = $data['rol_nombre'];
        }
        $this->carnet      = $data['carnet'] ?? null;
        $this->cui      = $data['cui'] ?? null;
        $this->registro_personal      = $data['registro_personal'] ?? null;
        $this->extension      = $data['extension'] ?? null;
        //$this->tipo_id      = $data['tipo_id'] ?? null;
    }
    //Convierte el objeto User a un arreglo asociativo
    public function getArrayCopy(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'password' => $this->password,
            'estado' => $this->estado,
            'correo' => $this->correo,
            'rol_id' => $this->rol_id,
            'carnet' => $this->carnet,
            'cui' => $this->cui,
            'registro_personal' => $this->registro_personal,
            'extension' => $this->extension,
        ];
    }
}
