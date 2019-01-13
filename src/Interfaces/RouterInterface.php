<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

use CrazyGoat\Core\Exceptions\RouteNotFound;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /**
     * @param RequestInterface $request
     * @return RouteInterface
     * @throws RouteNotFound
     */
    public function dispatch(ServerRequestInterface $request): RouteInterface;
}