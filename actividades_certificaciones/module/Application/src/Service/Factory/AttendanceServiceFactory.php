<?php

namespace Application\Service\Factory;

use Application\Service\AttendanceService;
use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;
use Application\Model\StudentAttendanceTable;
use Application\Model\StudentTable;
use Application\Service\ActivityService;
use Psr\Container\ContainerInterface;
use Application\Model\CarreraEstudianteTable;
use Application\Model\InscripcionTable;
use Application\Service\EstudianteService;


class AttendanceServiceFactory
{
    public function __invoke(ContainerInterface $container): AttendanceService
    {
        return new AttendanceService(
            $container->get(AttendanceListTable::class),
            $container->get(AttendanceListStudentTable::class),
            $container->get(StudentAttendanceTable::class),
            $container->get(StudentTable::class),
            $container->get(ActivityService::class),
            $container->get(CarreraEstudianteTable::class),
            $container->get(InscripcionTable::class),
            $container->get(EstudianteService::class)
        );
    }
}
