<?php

namespace Application\Model\Factory;

use Application\Model\CarreraEstudiante;
use Application\Model\CarreraEstudianteTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;

class CarreraEstudianteTableFactory
{
    public function __invoke(ContainerInterface $container): CarreraEstudianteTable
    {
        /** @var AdapterInterface $dbAdapter */
        $dbAdapter = $container->get('db2');
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new CarreraEstudiante());
        $tableGateway = new TableGateway('carrera_estudiante', $dbAdapter, null, $resultSetPrototype);
        return new CarreraEstudianteTable($tableGateway);
    }
}
