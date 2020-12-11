<?php

namespace Octoper\Lighthouse\Exceptions;

class AuditFailedException extends \Exception
{
    protected ?string $output;

    public function __construct(string $url, string $output = null)
    {
        parent::__construct("Audit of '{$url}' failed");

        $this->output = $output;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }
}
