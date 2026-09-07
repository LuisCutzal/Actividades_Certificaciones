<?php

namespace Application\Model\Factory;

use Application\Model\StudentAttendanceTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class StudentAttendanceTableFactory
{
    public function __invoke(ContainerInterface $container): StudentAttendanceTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('student_attendance', $adapter);

        return new StudentAttendanceTable($tableGateway);
    }
}
