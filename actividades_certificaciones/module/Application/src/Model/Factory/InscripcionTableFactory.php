<?php

namespace Application\Model\Factory;

use Application\Model\InscripcionTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class InscripcionTableFactory
{
    public function __invoke(ContainerInterface $container): InscripcionTable
    {
        /** @var AdapterInterface $adapter */
        $adapter = $container->get('db2');
        $tableGateway = new TableGateway('inscripcion', $adapter);

        return new InscripcionTable($tableGateway);
    }
}
