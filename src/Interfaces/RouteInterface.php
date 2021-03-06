<?php
declare(strict_types=1);

namespace CrazyGoat\Core\Interfaces;

interface RouteInterface
{
    public function getHandler(): string;

    public function getName(): ?string;

    public function getMiddlewares(): array;

    public function getAttributes(): array;
}