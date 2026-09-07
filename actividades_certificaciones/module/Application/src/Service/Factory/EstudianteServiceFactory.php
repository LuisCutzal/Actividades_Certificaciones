<?php

namespace Application\Service\Factory;

use Application\Service\EstudianteService;
use Application\Model\StudentTable;
use Application\Model\StudentAttendanceTable;
use Psr\Container\ContainerInterface;
use Application\Model\AttendanceListStudentTable;
use Application\Model\CarreraEstudianteTable;

class EstudianteServiceFactory
{
    public function __invoke(ContainerInterface $container): EstudianteService
    {
        return new EstudianteService(
            $container->get(StudentTable::class),
            $container->get(AttendanceListStudentTable::class),
            $container->get(CarreraEstudianteTable::class),
        );
    }
}

