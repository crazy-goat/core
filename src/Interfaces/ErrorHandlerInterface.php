<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

interface ErrorHandlerInterface
{
    public function processError(\Exception $exception): void;
}