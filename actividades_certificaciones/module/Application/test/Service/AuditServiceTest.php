<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Service\AuditService;
use Application\Model\AuditLogTable;


class AuditServiceTest extends TestCase
{
    private AuditService $auditService;
    private AuditLogTable $auditlogTable;

    protected function setUp(): void
    {
        $this->auditlogTable = $this->createMock(AuditLogTable::class);

        $this->auditService = new AuditService(
            $this->auditlogTable
        );
    }

    //log

    private function makeLogData(?array $details = null): array
    {
        return [
            'user_id' => 1,
            'user' => 'luis',
            'action' => 'crear',
            'entity_type' => 'actividad',
            'entity_id' => 10,
            'description' => 'actividad creada',
            'details' => $details === null
                ? null
                : json_encode($details),
        ];
    }

    public function testLogConDetalle(): void
    {
        // define el detalle que sera convertido a json
        $details = [
            'actividad' => 10,
            'estado' => 'creada'
        ];

        // verifica que save sea llamado una vez
        $this->auditlogTable->expects($this->once())
            ->method('save')
            ->with($this->makeLogData($details));

        // ejecuta el metodo a probar
        $this->auditService->log(
            1,
            'luis',
            'crear',
            'actividad',
            10,
            'actividad creada',
            $details
        );
    }

    public function testLogSinDetalles(): void
    {
        // verifica que save sea llamado una vez
        $this->auditlogTable->expects($this->once())
            ->method('save')
            ->with($this->makeLogData());

        // ejecuta el metodo sin enviar detalles
        $this->auditService->log(
            1,
            'luis',
            'crear',
            'actividad',
            10,
            'actividad creada',
            null
        );
    }

    public function testGetLogs(): void
    {
        // define los datos que retornara la tabla
        $logs = [
            [
                'id' => 1,
                'action' => 'crear',
                'entity_type' => 'actividad',
                'entity_id' => 10,
            ],
            [
                'id' => 2,
                'action' => 'editar',
                'entity_type' => 'actividad',
                'entity_id' => 10,
            ],
        ];

        // verifica que fetchLogs sea llamado una vez
        $this->auditlogTable->expects($this->once())
            ->method('fetchLogs')
            // verifica que los filtros sean enviados correctamente
            ->with(
                'actividad',
                10,
                'crear'
            )
            // simula la respuesta de la tabla
            ->willReturn($logs);

        // ejecuta el metodo a probar
        $resultado = $this->auditService->getLogs(
            'actividad',
            10,
            'crear'
        );

        // verifica que el resultado sea el esperado
        $this->assertSame($logs, $resultado);
    }
}

/*

vendor/bin/phpunit module/Application/test/Service/AuditServiceTest.php

*/