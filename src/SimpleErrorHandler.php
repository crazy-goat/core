<?php
declare(strict_types=1);

namespace CrazyGoat\Core;

use CrazyGoat\Core\Exceptions\RouteNotFound;
use CrazyGoat\Core\Interfaces\ErrorHandlerInterface;

final class SimpleErrorHandler implements ErrorHandlerInterface
{
    public function processError(\Exception $exception): void
    {
        if ($exception instanceof RouteNotFound) {
            $this->processRouteNotFound($exception);
        }
        $this->processException($exception);
    }

    private function processRouteNotFound(RouteNotFound $exception): void
    {
        header("HTTP/1.0 404 Not Found");
        echo '<h1>404 - Page not found.</h1>';
    }

    private function processException(\Exception $exception): void
    {
        header("HTTP/1.0 500 Internal Server Error");
        echo sprintf(
            '<h1>Fatal error "%s"<br/> in %s:%d</h1><br/>Stack trace:<br/><pre>%s</pre>',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            htmlspecialchars($exception->getTraceAsString())
        );
    }
}