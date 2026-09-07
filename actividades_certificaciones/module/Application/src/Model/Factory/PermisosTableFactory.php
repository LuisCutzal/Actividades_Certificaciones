<?php

namespace Application\Model\Factory;

use Application\Model\PermisosTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class PermisosTableFactory
{
    public function __invoke(ContainerInterface $container): PermisosTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('permisos', $adapter);

        return new PermisosTable($tableGateway);
    }
}
