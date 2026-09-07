<?php

namespace Application\Service\Factory;

use Application\Service\PersonalService;
use Application\Service\UserService;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;

class PersonalServiceFactory
{
    public function __invoke(ContainerInterface $container): PersonalService
    {
        $userService = $container->get(UserService::class);
        $activityService = $container->get(ActivityService::class);

        return new PersonalService($userService, $activityService);
    }
}
