<?php

namespace Application\Model\Factory;

use Application\Model\RolCarreraTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class RolCarreraTableFactory
{
    public function __invoke(ContainerInterface $container): RolCarreraTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('rol_carrera', $adapter);

        return new RolCarreraTable($tableGateway);
    }
}
