<?php

 namespace Authentication\Form\InputFilter\Factory;

use Authentication\Form\InputFilter\RegisterInputfilter;
use General\Service\GeneralService;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RegisterInputfilterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (! $container->get("general_service")) {
            throw new \InvalidArgumentException("Register inputfilter cannot retireve general service");
        }
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");

        $inputFilter = new RegisterInputfilter($generalService->getEm());
        // var_dump($generalService->getEm());
        // var_dump("Got here");
        // die();
        $inputFilter->setEntityManager($generalService->getEm());
        return $inputFilter;
    }
}
