<?php

namespace Application\Service\Factory;

use Application\Service\AsociacionService;
use Application\Service\UserService;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;

class AsociacionServiceFactory
{
    public function __invoke(ContainerInterface $container): AsociacionService
    {
        $userService = $container->get(UserService::class);
        $activityService = $container->get(ActivityService::class);

        return new AsociacionService($userService, $activityService);
    }
}
