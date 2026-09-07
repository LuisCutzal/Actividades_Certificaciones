<?php

namespace Application\Model\Factory;

use Application\Model\AttendanceListTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class AttendanceListTableFactory
{
    public function __invoke(ContainerInterface $container): AttendanceListTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('attendance_list', $adapter);

        return new AttendanceListTable($tableGateway);
    }
}
