<?php
declare(strict_types=1);

namespace CrazyGoat\Core;

use CrazyGoat\Core\Exceptions\HandlerNotFound;
use CrazyGoat\Core\Exceptions\InvalidConfigException;
use CrazyGoat\Core\Exceptions\RouteNotFound;
use CrazyGoat\Core\Interfaces\ControllerInterface;
use CrazyGoat\Core\Interfaces\ErrorHandlerInterface;
use CrazyGoat\Core\Interfaces\ResponseRendererInterface;
use CrazyGoat\Core\Interfaces\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class App
{
    /**
     * @var ?ErrorHandlerInterface
     */
    protected $errorHandler = null;

    /**
     * @var ?RouterInterface
     */
    protected $router = null;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ?ResponseRendererInterface
     */
    protected $renderer = null;

    /**
     * @var ?Closure
     */
    protected $requestFactory = null;

    /**
     * @var ?Closure
     */
    protected $responseFactory = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run(): void
    {
        try {
            $response = $this->process($this->getRequest(), $this->getResponse());
        } catch (\Exception $exception) {
            $response = $this->getErrorHandler()->processError($exception, $this->getResponse());
        }
        $this->respond($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HandlerNotFound
     * @throws InvalidConfigException
     * @throws RouteNotFound
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $route = $this->getRouter()->dispatch($request);

        if ($this->container->has($route->getHandler())) {
            $handler = $this->container->get($route->getHandler());

            if ($handler instanceof ControllerInterface) {
                $controller = new Controller($handler);
            } elseif ($handler instanceof \Closure) {
                $controller = new ClosureController($handler);
            } else {
                throw new \RuntimeException('Controller must be Closure or instance of ControllerInterface');
            }
            
            foreach ($route->getAttributes() as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }

            foreach (array_reverse($route->getMiddlewares()) as $middleware) {
                $controller->addMiddleware($this->container->get($middleware));
            }

            return $controller->callMiddlewareStack($request, $response);
        } else {
            throw new HandlerNotFound(sprintf('Handler %s not found.', $route->getHandler()));
        }
    }

    /**
     * @return ServerRequestInterface
     * @throws InvalidConfigException
     */
    protected function getRequest(): ServerRequestInterface
    {
        if ($this->requestFactory === null) {
            if ($this->container->has('requestFactory')) {
                $requestFactory = $this->container->get('requestFactory');
                if ($requestFactory instanceof \Closure) {
                    $this->requestFactory = $requestFactory;
                }
            } else {
                throw new InvalidConfigException('Request factory not set.');
            }
        }

        $request = ($this->requestFactory)();

        if ($request instanceof ServerRequestInterface) {
            return $request;
        }

        throw new InvalidConfigException('Request factory must be instance of ServerRequestInterface.');
    }

    /**
     * @return ResponseInterface
     * @throws InvalidConfigException
     */
    protected function getResponse(): ResponseInterface
    {
        if ($this->responseFactory === null) {
            if ($this->container->has('responseFactory')) {
                $responseFactory = $this->container->get('responseFactory');
                if ($responseFactory instanceof \Closure) {
                    $this->responseFactory = $responseFactory;
                }
            } else {
                throw new InvalidConfigException('Response factory not set.');
            }
        }
        
        $response = ($this->responseFactory)();

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        throw new InvalidConfigException('Response factory must be instance of ResponseInterface.');
    }

    /**
     * @return RouterInterface
     * @throws InvalidConfigException
     */
    protected function getRouter(): RouterInterface
    {
        if ($this->router === null) {
            if ($this->container->has('router')) {
                $router = $this->container->get('router');
                if ($router instanceof RouterInterface) {
                    $this->router = $router;
                } else {
                    throw new InvalidConfigException('Router must be instance of ResponseInterface.');
                }
            }
        }

        return $this->router;
    }

    protected function getErrorHandler(): ErrorHandlerInterface
    {
        if ($this->errorHandler === null) {
            if ($this->container->has('errorHandler')) {
                $errorHandler = $this->container->get('errorHandler');
                if ($errorHandler instanceof ErrorHandlerInterface) {
                    $this->errorHandler = $errorHandler;
                    return $this->errorHandler ;
                }
            } else {
                throw new InvalidConfigException('No error handler set');
            }

        }
        return $this->errorHandler;
    }

    protected function respond(ResponseInterface $response)
    {
        $this->getResponseRenderer()->render($response);
        exit();
    }

    protected function getResponseRenderer(): ResponseRendererInterface
    {
        if ($this->renderer === null) {
            if ($this->container->has('responseRenderer')) {
                $rendererObject = $this->container->get('responseRenderer');
                if ($rendererObject instanceof ResponseRendererInterface) {
                    $this->renderer = $rendererObject;
                    return $this->renderer;
                }
            } else {
                throw new InvalidConfigException('No response renderer set');
            }
        }

        return $this->renderer;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}