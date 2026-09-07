<?php

namespace Application\Service\Factory;

use Application\Service\UserService;
use Application\Model\UserTable;
use Psr\Container\ContainerInterface;
use Application\Model\RolTable;
use Application\Model\StudentTable;
use Application\Model\InscripcionTable;
use Application\Service\AuditService;
use Application\Model\ExtensionTable;
use Application\Model\RolCarreraTable;

class UserServiceFactory
{
    public function __invoke(ContainerInterface $container): UserService
    {
        return new UserService(
            $container->get(UserTable::class),
            $container->get(RolTable::class),
            $container->get(StudentTable::class),
            $container->get(InscripcionTable::class),
            $container->get(AuditService::class),
            $container->get(ExtensionTable::class),
            $container->get(RolCarreraTable::class),

        );
    }
}
