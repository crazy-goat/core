<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        \Closure $next = null
    ): ResponseInterface;
}