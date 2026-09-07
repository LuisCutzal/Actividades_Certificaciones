<?php

//Sin esta factory, Laminas no sabría cómo construir PasswordResetTable.
declare(strict_types=1);

namespace Application\Model\Factory;

use Psr\Container\ContainerInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Application\Model\PasswordResetTable;

class PasswordResetTableFactory
{
    //Método invocable. Laminas lo ejecuta cuando necesita crear PasswordResetTable
    public function __invoke(ContainerInterface $container)
    {
        //Obtener el adaptador de base de datos desde el contenedor
        $dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
        //Crear un ResultSet que definirá el formato de las filas devueltas
        $resultSetPrototype = new ResultSet();
        // Usamos ArrayObject para representar cada fila como arreglo/objeto
        $resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        /*
        Crear un TableGateway para la tabla "password_resets"
         Este gateway manejará SELECT, INSERT, UPDATE y DELETE.
        */
        $tableGateway = new TableGateway(
            'password_resets',  // nombre de la tabla
            $dbAdapter, // conexión a DB
            null, // no usamos funciones especiales
            $resultSetPrototype // cómo devolver resultados
        );

        // Crear la clase PasswordResetTable y pasarle:
        //    - el gateway (para operaciones básicas)
        //    - el adaptador (para consultas personalizadas)
        return new PasswordResetTable($tableGateway, $dbAdapter);
    }
}
