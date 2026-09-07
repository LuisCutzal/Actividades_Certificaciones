<?php

namespace Application\Model;

class Extension
{
    public ?int $extension = null;
    public ?string $nombre = null;

    public function exchangeArray(array $data): void
    {
        $this->extension = $data['extension'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
    }
}
