<?php

namespace Application\Service\Factory;

use Application\Model\AuditLogTable;
use Application\Service\AuditService;
use Psr\Container\ContainerInterface;

class AuditServiceFactory
{
    public function __invoke(ContainerInterface $container): AuditService
    {
        $auditLogTable = $container->get(AuditLogTable::class);

        return new AuditService($auditLogTable);
    }
}