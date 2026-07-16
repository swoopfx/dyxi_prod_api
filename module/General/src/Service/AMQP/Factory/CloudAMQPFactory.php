<?php

namespace General\Service\AMQP\Factory;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use General\Service\AMQP\CloudAMQP;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CloudAMQPFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $cloud = new CloudAMQP();
        $config = $container->get("config");
        $amqpConnect = new AmqpConnectionFactory($config["cloud_amqp"]["dns"]);
        $cloud->setAmqpConnect($amqpConnect);
        return $cloud;
    }
}
