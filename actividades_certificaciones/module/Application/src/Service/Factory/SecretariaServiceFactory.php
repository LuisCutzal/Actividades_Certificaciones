<?php

namespace Application\Service\Factory;

use Application\Service\SecretariaService;
use Application\Service\UserService;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;

class SecretariaServiceFactory
{
    public function __invoke(ContainerInterface $container): SecretariaService
    {
        $userService = $container->get(UserService::class);
        $activityService = $container->get(ActivityService::class);

        return new SecretariaService($userService, $activityService);
    }
}
