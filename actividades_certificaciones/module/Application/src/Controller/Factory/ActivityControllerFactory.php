<?php

namespace Application\Controller\Factory;

use Application\Controller\ActivityController;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;
use Application\Service\AttendanceService;
use Laminas\Db\Adapter\Adapter;
use Application\Model\RolTable;
use Application\Model\InscripcionTable;
use Application\Model\CarreraTable;
//use Application\Model\CarreraEstudianteTable;
use Application\Model\ExtensionTable;
use Application\Service\AuditService;

class ActivityControllerFactory
{
    public function __invoke(ContainerInterface $container): ActivityController
    {
        return new ActivityController(
            $container->get(ActivityService::class),
            $container->get(AttendanceService::class),
            $container->get(Adapter::class),
            $container->get(ExtensionTable::class),
            $container->get(RolTable::class),
            $container->get(CarreraTable::class),
            //$container->get(CarreraEstudianteTable::class),
            $container->get(AuditService::class),
            $container->get(InscripcionTable::class),


        );
    }
}
