<?php

namespace Application\Model\Factory;

use Application\Model\StudentTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class StudentTableFactory
{
    public function __invoke(ContainerInterface $container): StudentTable
    {
        /** @var AdapterInterface $adapter */
        $adapter = $container->get('db2');
        $tableGateway = new TableGateway('estudiante', $adapter);

        return new StudentTable($tableGateway);
    }
}
