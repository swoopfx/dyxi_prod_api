<?php

namespace Authentication\Form\Fieldset\Factory;

use Authentication\Form\Fieldset\UserFieldset;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use General\Service\GeneralService;

class UserFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $form = new UserFieldset();
        if (! $container->has("general_service")) {
            throw new \InvalidArgumentException("User Fieldset Factory cannot retrive the general Service");
        }
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $form->setEntityManager($generalService->getEm());

        return $form;
    }
}
