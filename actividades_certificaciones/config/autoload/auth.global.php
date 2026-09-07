<?php
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Db\Adapter\Adapter as DbAdapter;

return [
    'service_manager' => [
        'factories' => [
            // AuthenticationService
            AuthenticationService::class => function($container) {
                $dbAdapter = $container->get(DbAdapter::class);

                // adapter: tabla users, columna identidad = email, credential = password_hash
                $authAdapter = new CallbackCheckAdapter(
                    $dbAdapter,
                    'users',
                    'email',
                    'password_hash',
                    function ($dbCredential, $providedCredential) {
                        // $dbCredential es el password_hash de la DB
                        return password_verify($providedCredential, $dbCredential);
                    }
                );

                $authService = new AuthenticationService(null, $authAdapter);
                return $authService;
            },

            // también asegurar que DbAdapter esté en el container (normalmente sí)
            DbAdapter::class => function($c) {
                $config = $c->get('config')['db'] ?? null;
                return new \Laminas\Db\Adapter\Adapter($config);
            },
        ],
    ],
];
