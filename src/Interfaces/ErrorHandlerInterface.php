<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ErrorHandlerInterface
{
    public function processError(\Exception $exception, ResponseInterface $response): ResponseInterface;
}