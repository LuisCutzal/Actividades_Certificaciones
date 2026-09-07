<?php

namespace Application\Controller\Factory;

use Application\Controller\EstudianteController;
use Application\Service\EstudianteService;
use Psr\Container\ContainerInterface;

class EstudianteControllerFactory
{
    public function __invoke(ContainerInterface $container): EstudianteController
    {
        return new EstudianteController(
            $container->get(EstudianteService::class)
        );
    }
}
