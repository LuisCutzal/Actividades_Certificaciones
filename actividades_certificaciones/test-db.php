<?php

use Laminas\Db\Adapter\Adapter;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Cargar .env manualmente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Cargar la config de DB desde tu env.local.php
$config = include __DIR__ . '/config/autoload/env.local.php';

// Crear adapter
$adapter = new Adapter($config['db']);

try {
    // Ejecutar una consulta simple
    $result = $adapter->query('SELECT 1 AS test', Adapter::QUERY_MODE_EXECUTE);
    print_r($result->current());
    echo "\n\n✔ Conexión a MySQL exitosa.\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}



//para ejecutar el archivo -> php test-db.php