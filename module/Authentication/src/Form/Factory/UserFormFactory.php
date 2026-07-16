<?php

namespace Authentication\Form\Factory;

use Authentication\Form\UserForm;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $form = new UserForm();

        return $form;
    }
}
