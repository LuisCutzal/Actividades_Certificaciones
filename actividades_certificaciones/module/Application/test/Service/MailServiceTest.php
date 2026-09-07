<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;
use Application\Service\MailService;
use Laminas\Mail\Transport\Smtp;

class MailServiceTest extends TestCase
{
    private MailService $mailService;
    private Smtp $smtpMock;
    private array $config;

    protected function setUp(): void
    {
        $this->smtpMock = $this->createMock(Smtp::class);

        $this->config = [
            'from' => [
                'email' => 'noreply@test.com',
                'name'  => 'Sistema'
            ],
            'transport' => [
                'options' => [
                    'host' => 'smtp.test.com'
                ]
            ]
        ];

        $this->mailService = new MailService(
            $this->config,
            $this->smtpMock
        );
    }


    //send

    public function testSendSinExcepcion(): void
    {
        $this->smtpMock
            ->expects($this->once())
            ->method('send');

        $this->mailService->send(
            'destino@test.com',
            'asunto',
            'mensaje'
        );
    }

    public function testSendConNombreCompleto(): void
    {
        $this->smtpMock
            ->expects($this->once())
            ->method('send');

        $this->mailService->send(
            'destino@test.com',
            'asunto',
            'mensaje',
            'Juan',
            'Perez'
        );
    }

    public function testSendSoloNombre(): void
    {
        $this->smtpMock
            ->expects($this->once())
            ->method('send');

        $this->mailService->send(
            'destino@test.com',
            'asunto',
            'mensaje',
            'Juan',
            null
        );
    }

    public function testSendSinNombre(): void
    {
        $this->smtpMock
            ->expects($this->once())
            ->method('send');

        $this->mailService->send(
            'destino@test.com',
            'asunto',
            'mensaje',
            null,
            null
        );
    }

    public function testCapturaExcepcionSmtp(): void
    {
        $this->smtpMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('error smtp'));

        $this->mailService->send(
            'destino@test.com',
            'asunto',
            'mensaje'
        );
    }

}


/*

vendor/bin/phpunit module/Application/test/Service/MailServiceTest.php

*/