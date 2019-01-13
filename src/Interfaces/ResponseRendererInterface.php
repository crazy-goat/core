<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponseRendererInterface
{
    public function render(ResponseInterface $response): void;
}