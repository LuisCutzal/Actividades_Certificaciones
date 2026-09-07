<?php

namespace Application\Model\Factory;

use Application\Model\AttendanceListStudentTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;


class AttendanceListStudentTableFactory
{
    public function __invoke(ContainerInterface $container): AttendanceListStudentTable
    {
        $adapter = $container->get(AdapterInterface::class);
        /** @var array $config */
        $config = $container->get('config');
        $schema = (string) $config['db2']['schema'];
        $tableGateway = new TableGateway('attendance_list_student', $adapter);

        return new AttendanceListStudentTable($tableGateway,$schema);
    }
}
