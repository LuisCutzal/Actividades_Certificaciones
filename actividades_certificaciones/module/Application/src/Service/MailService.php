<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Transport\TransportInterface;

class MailService
{
    private TransportInterface $transport;
    private array $config;

    //public function __construct(array $config)

    /*
    permite inyectar smtp para tests sin conexion real
    mantiene comportamiento por defecto en produccion
    */
    public function __construct(array $config, ?TransportInterface $transport = null)
    {
        $this->config = $config;

        $this->transport = $transport ?? new Smtp(
            new SmtpOptions($config['transport']['options'])
        );
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $toName = null,
        ?string $toLastName = null
    ): void {

        $fullName = trim(($toName ?? '') . ' ' . ($toLastName ?? ''));

        $mailMsg = new Message();

        $mailMsg->addTo(
            $to,
            $fullName !== '' ? $fullName : null
        );

        $mailMsg->addFrom(
            $this->config['from']['email'],
            $this->config['from']['name']
        );

        $mailMsg->setSubject($subject);
        $mailMsg->setBody($body);
        $mailMsg->setEncoding('UTF-8');

        try {
            $this->transport->send($mailMsg);
        } catch (\Exception $e) {
            error_log("ERROR SMTP: " . $e->getMessage());
        }
    }
}
