<?php
declare(strict_types=1);

namespace Application\Controller\Plugin\Factory;

use Application\Controller\Plugin\AuthPlugin;
use Psr\Container\ContainerInterface;

class AuthPluginFactory
{
    public function __invoke(ContainerInterface $container): AuthPlugin
    {
        return new AuthPlugin();
    }
}
