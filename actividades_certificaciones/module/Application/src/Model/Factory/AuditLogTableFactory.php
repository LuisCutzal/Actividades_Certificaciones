<?php

namespace Application\Model\Factory;

use Application\Model\AuditLogTable;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

class AuditLogTableFactory
{
    public function __invoke(ContainerInterface $container): AuditLogTable
    {
        $adapter = $container->get(AdapterInterface::class);
        $tableGateway = new TableGateway('audit_log', $adapter);

        return new AuditLogTable($tableGateway);
    }
}
