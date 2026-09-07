<?php

namespace Application\Controller\Factory;

use Application\Controller\AdminController;
use Application\Service\AdminService;
use Psr\Container\ContainerInterface;
use Application\Model\RolTable;
use Application\Service\ActivityService;
use Application\Model\ExtensionTable;
use Application\Service\AuditService;

class AdminControllerFactory
{
    public function __invoke(ContainerInterface $container): AdminController
    {
        return new AdminController(
            $container->get(AdminService::class),
            $container->get(RolTable::class),
            $container->get(ActivityService::class),
            $container->get(ExtensionTable::class),
            $container->get(AuditService::class)
        );
    }
}
