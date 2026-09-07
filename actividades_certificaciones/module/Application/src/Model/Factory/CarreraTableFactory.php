<?php

namespace Application\Model\Factory;

use Application\Model\Carrera;
use Application\Model\CarreraTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;

class CarreraTableFactory
{
    public function __invoke(ContainerInterface $container): CarreraTable
    {
        /** @var AdapterInterface $dbAdapter */
        $dbAdapter = $container->get('db2');
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Carrera());
        $tableGateway = new TableGateway('carrera', $dbAdapter, null, $resultSetPrototype);
        return new CarreraTable($tableGateway);
    }
}
