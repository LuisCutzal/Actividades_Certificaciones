<?php

namespace Application\Model\Factory;

use Application\Model\RolesPermisosTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class RolesPermisosTableFactory
{
    public function __invoke(ContainerInterface $container): RolesPermisosTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('roles_permisos', $adapter);

        return new RolesPermisosTable($tableGateway);
    }
}
