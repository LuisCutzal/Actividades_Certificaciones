<?php

namespace Application\Model\Factory;

use Application\Model\Activity;
use Application\Model\ActivityTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class ActivityTableFactory
{
    /**
     * Factory para construir la clase ActivityTable.
     *
     * Laminas utiliza factories para inicializar clases que requieren dependencias.
     * En este caso, ActivityTable necesita:
     *  - Un TableGateway configurado para la tabla "actividad".
     *  - Un ResultSet que transforme cada fila en un objeto Actividad.
     */
    public function __invoke(ContainerInterface $container): ActivityTable
    {
        // Obtiene el adaptador de base de datos desde el contenedor de dependencias.
        $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
        // Configura el ResultSet para mapear las filas SQL al objeto Actividad.
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Activity());
        /** @var array $config */
        $config = $container->get('config');
        $schema = (string) $config['db2']['schema'];
        /**
         * El TableGateway es la clase central de Laminas para interactuar 
         * con una tabla SQL específica. Aquí se configura para:
         *  - Usar la tabla "Actividad"
         *  - Utilizar el adaptador de conexión
         *  - Mapear los resultados al prototipo definido arriba
         */
        $tableGateway = new TableGateway('actividad', $dbAdapter, null, $resultSetPrototype);
        // Retorna la instancia de UserTable construida con el TableGateway ya preparado.
        //el nombre debe de ser igual a la tabla que esta en la bd
        return new ActivityTable($tableGateway, $schema);
    }
}
