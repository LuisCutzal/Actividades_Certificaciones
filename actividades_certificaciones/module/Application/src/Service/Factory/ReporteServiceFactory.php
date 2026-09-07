<?php

namespace Application\Service\Factory;

use Application\Service\ReporteService;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;
use Application\Model\StudentTable;
use Application\Service\EstudianteService;
//use Application\Model\InscripcionTable;
use Application\Model\CarreraTable;
use Application\Model\RolTable;

class ReporteServiceFactory
{
    public function __invoke(ContainerInterface $container): ReporteService
    {
        return new ReporteService(
            $container->get(ActivityService::class),
            $container->get(StudentTable::class),
            $container->get(EstudianteService::class),
            //$container->get(InscripcionTable::class),
            $container->get(CarreraTable::class),
            $container->get(RolTable::class)
        );
    }
}

