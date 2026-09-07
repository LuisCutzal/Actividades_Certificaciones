<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;

class TipoTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    // Obtener un tipo por id
    public function getTipoById(int $id): ?Tipo
    {
        $row = $this->tableGateway->select(['id' => $id])->current();
        if (!$row) return null;

        $tipo = new Tipo();
        $tipo->exchangeArray((array)$row);
        return $tipo;
    }

    // Obtener todos los tipos
    public function fetchAll(): array
    {
        $tipos = [];
        foreach ($this->tableGateway->select() as $row) {
            $tipo = new Tipo();
            $tipo->exchangeArray((array)$row);
            $tipos[] = $tipo;
        }
        return $tipos;
    }

    // Opcional: buscar tipo por nombre
    public function getTipoByNombre(string $nombre): ?Tipo
    {
        $row = $this->tableGateway->select(['nombre' => $nombre])->current();
        if (!$row) return null;

        $tipo = new Tipo();
        $tipo->exchangeArray((array)$row);
        return $tipo;
    }
}
