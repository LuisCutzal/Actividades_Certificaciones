<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Service\UserService;
use Application\Service\ActivityService;

class SecretariaService
{
    private UserService $userService;
    private ActivityService $activityService;

    public function __construct(UserService $userService, ActivityService $activityService)
    {
        $this->userService = $userService;
        $this->activityService = $activityService;
    }

}