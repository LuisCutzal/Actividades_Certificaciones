<?php
namespace Application\View\Helper\Factory;

use Application\View\Helper\UserIdentity;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class UserIdentityFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new UserIdentity();
    }
}
