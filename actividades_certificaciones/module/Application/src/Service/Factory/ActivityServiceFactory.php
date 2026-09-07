<?php

namespace Application\Service\Factory;

use Application\Service\ActivityService;
use Application\Model\ActivityTable;
use Psr\Container\ContainerInterface;
use Application\Service\MailService;
use Application\Model\UserTable;
use Application\Model\RolTable;
use Application\Model\CarreraTable;
use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;

class ActivityServiceFactory
{
    public function __invoke(ContainerInterface $container): ActivityService
    {
        return new ActivityService(
            $container->get(ActivityTable::class),
            $container->get(MailService::class),
            $container->get(UserTable::class),
            $container->get(RolTable::class),
            $container->get(AttendanceListTable::class),
            $container->get(AttendanceListStudentTable::class),
            $container->get(CarreraTable::class),
        );
    }
}
