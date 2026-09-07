<?php
//una Factory es la clase encargada de crear una instancia configurada de un servicio.
declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\UserTable;
use Application\Model\PermisosTable;
use Application\Model\RolCarreraTable;
use Application\Service\AuthService;
use Psr\Container\ContainerInterface;
use Application\Model\RolTable;
use Application\Model\TipoTable;
use Application\Model\RolesPermisosTable;



class AuthServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthService
    {
        // Obtener la dependencia: UserTable
        // Esto funciona porque definimos UserTableFactory previamente
        $userTable = $container->get(UserTable::class);
        // Obtener la dependencia: PermisosTable
        $permisosTable = $container->get(PermisosTable::class);
        // Obtener la dependencia: RolCarreraTable
        $rolCarreraTable = $container->get(RolCarreraTable::class);
        // Obtener la dependencia: RolTable
        $rolTable = $container->get(RolTable::class);
        // Obtener la dependencia: TipoTable
        $tipoTable = $container->get(TipoTable::class);
        // Obtener la dependencia: RolesPermisosTable
        $rolesPermisosTable = $container->get(RolesPermisosTable::class);
         // Retornar AuthService inyectando su dependencia
        return new AuthService($userTable, $permisosTable, $rolCarreraTable, $rolTable, $tipoTable, $rolesPermisosTable);
    }
}
