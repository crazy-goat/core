<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}