<?php

declare(strict_types=1);

namespace Evaluation\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Evaluation\Controller\KaufmanDyslexiaController;

class KaufmanDyslexiaControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new KaufmanDyslexiaController();
    }
}
