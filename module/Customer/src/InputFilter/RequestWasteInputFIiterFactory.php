<?php

namespace Customer\InputFilter;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RequestWasteInputFilterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (! $container->has("general_service")) {
        }
        $generalService = $container->get("general_service");
        $inputFilter = new RequestWasteInputFilter($generalService->getEm());
        return $inputFilter;
    }
}
