<?php

namespace Application\Controller\Factory;

use Application\Controller\ReporteController;
use Application\Service\ReporteService;
use Psr\Container\ContainerInterface;
use Application\Model\ReportePdfTable;
use Laminas\View\Renderer\PhpRenderer;
use Application\Service\AuditService;

class ReporteControllerFactory
{
    public function __invoke(ContainerInterface $container): ReporteController
    {
        return new ReporteController(
            $container->get(ReporteService::class),
            $container->get(ReportePdfTable::class),
            $container->get(PhpRenderer::class),
            $container->get(AuditService::class)
        );
    }
}
