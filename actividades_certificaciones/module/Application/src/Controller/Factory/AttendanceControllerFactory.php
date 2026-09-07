<?php

namespace Application\Controller\Factory;

use Application\Controller\AttendanceController;
use Application\Service\AttendanceService;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;

class AttendanceControllerFactory
{
    public function __invoke(ContainerInterface $container): AttendanceController
    {
        return new AttendanceController(
            $container->get(AttendanceService::class),
            $container->get(ActivityService::class),
        );
    }
}
