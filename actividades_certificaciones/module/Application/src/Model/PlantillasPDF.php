<?php

namespace Application\Model;


class PlantillasPDF
{   // Propiedades públicas del usuario. 
    public ?int $id = null;
    public ?string $codigo = null;
    public ?string $nombre = null;

    public ?string $arte_contenido = null;
    public ?string $arte_final = null;

    public ?string $titulo = null;
    public ?string $texto_firma = null;
    public ?string $institucion = null;
    
    public ?string $estado = null;
    public ?string $created_at = null;

    public ?string $texto_constancia = null;
    public ?string $punto_acta = null;
    public ?string $inciso_acta = null;
    public ?string $numero_acta = null;
    public ?string $fecha_acta = null;


    // constructor para inicializar el objeto Use
    public function __construct(
        ?int $id = null,
        ?string $codigo = null,
        ?string $nombre = null,
        ?string $arte_contenido = null,
        ?string $arte_final = null,


        ?string $titulo = null,
        ?string $texto_firma = null,
        ?string $institucion = null,
        
        ?string $estado = null,
        ?string $created_at = null,
        ?string $texto_constancia = null,
        ?string $punto_acta = null,
        ?string $inciso_acta = null,
        ?string $numero_acta = null,
        ?string $fecha_acta = null
    ) {
        $this->id       = $id;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->arte_contenido = $arte_contenido;
        $this->arte_final = $arte_final;

        $this->titulo = $titulo;
        $this->texto_firma = $texto_firma;
        $this->institucion = $institucion;

        
        $this->estado = $estado;
        $this->created_at = $created_at;
        $this->texto_constancia = $texto_constancia;
        $this->punto_acta = $punto_acta;
        $this->inciso_acta = $inciso_acta;
        $this->numero_acta = $numero_acta;
        $this->fecha_acta = $fecha_acta;
    }
   
    public function exchangeArray(array $data): void
    {
        $this->id       = $data['id'] ?? null;
        $this->codigo   = $data['codigo'] ?? null;
        $this->nombre   = $data['nombre'] ?? null;
        $this->arte_contenido   = $data['arte_contenido'] ?? null;
        $this->arte_final   = $data['arte_final'] ?? null;
        
        $this->titulo   = $data['titulo'] ?? null;
        $this->texto_firma   = $data['texto_firma'] ?? null;
        $this->institucion   = $data['institucion'] ?? null;

        
        $this->estado   = $data['estado'] ?? null;
        $this->created_at   = $data['created_at'] ?? null;
        $this->texto_constancia   = $data['texto_constancia'] ?? null;
        $this->punto_acta   = $data['punto_acta'] ?? null;
        $this->inciso_acta   = $data['inciso_acta'] ?? null;
        $this->numero_acta   = $data['numero_acta'] ?? null;
        $this->fecha_acta   = $data['fecha_acta'] ?? null;
    }
    
    //Convierte el objeto User a un arreglo asociativo
    public function getArrayCopy(): array
    {
        return get_object_vars($this);
    }
}
