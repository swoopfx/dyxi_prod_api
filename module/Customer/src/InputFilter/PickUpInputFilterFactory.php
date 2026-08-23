<?php

namespace Customer\InputFilter;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PickUpInputFilterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $generalService = $container->get("general_service");
        $filter = new PickUpInputFilter();
        $filter->setEntityManager($generalService->getEm());
        return $filter;
    }
}
