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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
    private $container;

    /**
     * @var ?ResponseRendererInterface
     */
    private $renderer = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run(): void
    {
        try {
            $response = $this->process($this->getRequest(), $this->getResponse());
            $this->respond($response);
        } catch (\Exception $exception) {
            $this->getErrorHandler()->processError($exception);
            exit();
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HandlerNotFound
     * @throws InvalidConfigException
     * @throws RouteNotFound
     */
    public function process(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $route = $this->getRouter()->dispatch($request);

        if ($this->container->has($route->getHandler())) {
            $controller = new Controller($this->container->get($route->getHandler()));

            foreach (array_reverse($route->getMiddlewares()) as $middleware) {
                $controller->addMiddleware($this->container[$middleware]);
            }

            return $controller->callMiddlewareStack($request, $response);
        } else {
            throw new HandlerNotFound(sprintf('Handler %s not found.', $route->getHandler()));
        }
    }

    /**
     * @return RequestInterface
     * @throws InvalidConfigException
     */
    protected function getRequest(): RequestInterface
    {
        $request = null;
        if ($this->container->has('requestFactory')) {
            $request = $this->container->get('requestFactory');
            if ($request instanceof \Closure) {
                $request = $request();
            }
        }

        if ($request instanceof RequestInterface) {
            return $request;
        }

        throw new InvalidConfigException('Request factory must be instance of RequestInterface.');
    }

    /**
     * @return ResponseInterface
     * @throws InvalidConfigException
     */
    protected function getResponse(): ResponseInterface
    {
        $response = null;
        if ($this->container->has('responseFactory')) {
            $response = $this->container->get('responseFactory');
            if ($response instanceof \Closure) {
                $response = $response();
            }
        }

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
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            if ($router instanceof RouterInterface) {
                return $router;
            }
        }

        throw new InvalidConfigException('Router must be instance of ResponseInterface.');
    }

    protected function getErrorHandler(): ErrorHandlerInterface
    {
        if ($this->errorHandler === null) {
            if ($this->container->has('errorHandler')) {
                $errorHandler = $this->container->get('errorHandler');
                if ($errorHandler instanceof ErrorHandlerInterface) {
                    $this->errorHandler = $errorHandler;
                }
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
            }
            $this->renderer = new DefaultResponseRenderer();
        }

        return $this->renderer;
    }
}