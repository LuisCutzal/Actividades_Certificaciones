<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;
use Application\Model\PasswordResetTable;
use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use ArrayObject;

class PasswordResetTableTest extends TestCase
{
    private TableGatewayInterface $tableGateway;
    private Adapter $adapter;

    private PasswordResetTable $table;

    protected function setUp(): void
    {
        $this->tableGateway =
            $this->createMock(
                TableGatewayInterface::class
            );

        $this->adapter =
            $this->createMock(
                Adapter::class
            );

        $this->table = new PasswordResetTable(
            $this->tableGateway,
            $this->adapter
        );
    }

    public function testCreateResetTokenInsertsRecord(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with([
                'user_id' => 5,
                'token' => 'abc123',
                'expires_at' => '2026-01-01 10:00:00',
                'used' => 0
            ]);

        $this->table->createResetToken(
            5,
            'abc123',
            '2026-01-01 10:00:00'
        );
    }

    public function testGetByTokenReturnsRecord(): void
    {
        $row = new ArrayObject([
            'id' => 1,
            'user_id' => 5,
            'token' => 'abc',
            'used' => 0
        ]);

        $resultSet = $this->createMock(
            ResultSet::class
        );

        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->method('select')
            ->with([
                'token' => 'abc',
                'used' => 0
            ])
            ->willReturn($resultSet);

        $result = $this->table->getByToken(
            'abc'
        );

        $this->assertIsArray($result);

        $this->assertEquals(
            1,
            $result['id']
        );
    }

    public function testGetByTokenReturnsNull(): void
    {
        $resultSet = $this->createMock(
            ResultSet::class
        );

        $resultSet
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->method('select')
            ->willReturn($resultSet);

        $result = $this->table->getByToken(
            'invalid'
        );

        $this->assertNull($result);
    }

    public function testMarkAsUsedUpdatesRecord(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['used' => 1],
                ['id' => 10]
            );

        $this->table->markAsUsed(10);
    }

    public function testInvalidateOldTokensExecutesQuery(): void
    {
        // espera que tableGateway actualice tokens antiguos
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['used' => 1],
                ['user_id' => 5]
            );

        // crea tabla con mock del adapter
        $table = new PasswordResetTable(
            $this->tableGateway,
            $this->adapter
        );

        // ejecuta metodo real
        $table->invalidateOldTokens(5);
    }
}
/*

vendor/bin/phpunit module/Application/test/Model/PasswordResetTableTest.php


php vendor/bin/phpunit --filter testInvalidateOldTokensExecutesQuery



*/
