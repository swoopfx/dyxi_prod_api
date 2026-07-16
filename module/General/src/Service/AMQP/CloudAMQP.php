<?php

namespace General\Service\AMQP;

class CloudAMQP
{
    private $amqpConnect;

    public function connect()
    {
        try {
            return $this->amqpConnect;
        } catch (\Throwable $th) {
            //throw $th; log error
        }
    }


    /**
     * Get the value of amqpConnect
     */
    public function getAmqpConnect()
    {
        return $this->amqpConnect;
    }

    /**
     * Set the value of amqpConnect
     *
     * @return  self
     */
    public function setAmqpConnect($amqpConnect)
    {
        $this->amqpConnect = $amqpConnect;

        return $this;
    }
}
