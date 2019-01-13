<?php

namespace CrazyGoat\Core;

use CrazyGoat\Core\Interfaces\ControllerInterface;
use CrazyGoat\Core\Interfaces\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Controller implements MiddlewareInterface
{
    use Middleware;

    /**
     * @var ControllerInterface
     */
    private $controller;

    public function __construct(ControllerInterface $controller)
    {
        $this->controller = $controller;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ): ResponseInterface {
        return ($this->controller)($request,$response);
    }
}