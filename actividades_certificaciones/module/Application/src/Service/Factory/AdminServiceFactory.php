<?php

namespace Application\Service\Factory;

use Application\Service\AdminService;
use Application\Service\UserService;
use Psr\Container\ContainerInterface;
use Application\Model\PermisosTable;
use Application\Model\RolTable;
use Application\Model\RolesPermisosTable;
use Application\Model\RolCarreraTable;
use Application\Model\CarreraTable;
use Application\Model\TipoTable;


class AdminServiceFactory
{
    public function __invoke(ContainerInterface $container): AdminService
    {
        return new AdminService(
            $container->get(UserService::class),
            $container->get(PermisosTable::class),
            $container->get(RolTable::class),
            $container->get(RolesPermisosTable::class),
            $container->get(RolCarreraTable::class),
            $container->get(CarreraTable::class),
            $container->get(TipoTable::class)
        );
    }
}
