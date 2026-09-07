<?php

namespace Application\Model\Factory;

use Application\Model\User;
use Application\Model\UserTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

use Application\Model\RolTable;

class UserTableFactory
{
    /**
     * Factory para construir la clase UserTable.
     *
     * Laminas utiliza factories para inicializar clases que requieren dependencias.
     * En este caso, UserTable necesita:
     *  - Un TableGateway configurado para la tabla "usuarios".
     *  - Un ResultSet que transforme cada fila en un objeto User.
     */
    public function __invoke(ContainerInterface $container): UserTable
    {
        // Obtiene el adaptador de base de datos desde el contenedor de dependencias.
        $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
        // Configura el ResultSet para mapear las filas SQL al objeto User.
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new User());
        $rolTable = $container->get(RolTable::class);
        /**
         * El TableGateway es la clase central de Laminas para interactuar 
         * con una tabla SQL específica. Aquí se configura para:
         *  - Usar la tabla "usuarios"
         *  - Utilizar el adaptador de conexión
         *  - Mapear los resultados al prototipo definido arriba
         */
        $tableGateway = new TableGateway('usuarios', $dbAdapter, null, $resultSetPrototype);
        // Retorna la instancia de UserTable construida con el TableGateway ya preparado.
        return new UserTable($tableGateway, $rolTable);
    }
}
