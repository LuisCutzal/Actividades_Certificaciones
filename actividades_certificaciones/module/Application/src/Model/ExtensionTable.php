<?php

namespace Application\Model;

use Application\Model\Extension;
use Laminas\Db\TableGateway\TableGatewayInterface;


class ExtensionTable
{
    private TableGatewayInterface $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getById(int $id): ?Extension
    {
        $row = $this->tableGateway->select(['extension' => $id])->current();

        if (!$row) {
            return null;
        }

        $lista = new Extension();
        $lista->exchangeArray((array) $row);

        return $lista;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

}
