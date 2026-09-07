<?php

namespace Application\Controller\Factory;

use Application\Controller\UserController;
use Application\Service\UserService;
use Psr\Container\ContainerInterface;
use Application\Service\ActivityService;
use Application\Model\ExtensionTable;
use Application\Model\RolTable;

class UserControllerFactory
{
    public function __invoke(ContainerInterface $container): UserController
    {
        return new UserController(
            $container->get(UserService::class),
            $container->get(ActivityService::class),
            $container->get(ExtensionTable::class),
            $container->get(RolTable::class),
        );
    }
}
