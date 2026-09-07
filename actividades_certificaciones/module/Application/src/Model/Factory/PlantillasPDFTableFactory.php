<?php

namespace Application\Model\Factory;

use Application\Model\PlantillasPDFTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class PlantillasPDFTableFactory
{
    public function __invoke(ContainerInterface $container): PlantillasPDFTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('plantillas_pdf', $adapter);

        return new PlantillasPDFTable($tableGateway);
    }
}
