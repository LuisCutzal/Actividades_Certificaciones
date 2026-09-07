<?php

namespace Application\Model\Factory;

use Application\Model\ReportePdfTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class ReportePdfTableFactory
{
    public function __invoke(ContainerInterface $container): ReportePdfTable
    {
        $adapter = $container->get(AdapterInterface::class);

        $tableGateway = new TableGateway(
            'reporte_pdf',
            $adapter
        );
        /** @var array $config */
        $config = $container->get('config');
        $schema = (string) $config['db2']['schema'];

        return new ReportePdfTable($tableGateway, $schema);
    }
}
