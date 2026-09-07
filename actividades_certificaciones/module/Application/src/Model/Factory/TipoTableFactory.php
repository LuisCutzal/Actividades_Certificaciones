<?php

namespace Application\Model\Factory;

use Application\Model\TipoTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class TipoTableFactory
{
    public function __invoke(ContainerInterface $container): TipoTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('tipo', $adapter);

        return new TipoTable($tableGateway);
    }
}
