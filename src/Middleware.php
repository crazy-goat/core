<?php
declare(strict_types=1);

namespace CrazyGoat\Core;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

trait Middleware
{
    /**
     * @var callable
     */
    protected $top;

    public function addMiddleware(callable $callable)
    {
        if (is_null($this->top)) {
            $this->initStack();
        }
        $next = $this->top;
        $this->top = function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use (
            $callable,
            $next
        ) {
            $result = call_user_func($callable, $request, $response, $next);
            if ($result instanceof ResponseInterface === false) {
                throw new \RuntimeException(
                    'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                );
            }

            return $result;
        };

        return $this;
    }

    protected function initStack(callable $kernel = null)
    {
        if (!is_null($this->top)) {
            throw new \RuntimeException('MiddlewareStack can only be seeded once.');
        }

        $this->top = $kernel ?? $this;
    }

    public function callMiddlewareStack(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (is_null($this->top)) {
            $this->initStack();
        }

        $start = $this->top;
        return $start($request, $response);
    }
}
