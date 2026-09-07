<?php

namespace Application\Model\Factory;

use Application\Model\ExtensionTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class ExtensionTableFactory
{
    public function __invoke(ContainerInterface $container): ExtensionTable
    {
        /** @var AdapterInterface $adapter */
        $adapter = $container->get('db2');
        $tableGateway = new TableGateway('extension', $adapter);

        return new ExtensionTable($tableGateway);
    }
}
