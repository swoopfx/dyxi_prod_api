<?php

namespace General\Service\Pusher;

use Pusher\Pusher;

class PusherService
{
    // const

    private $pusherObject;

    public function pusherTrigger()
    {
    }



    /**
     * Get the value of pusherObject
     * @return Pusher
     */
    public function getPusherObject()
    {
        return $this->pusherObject;
    }

    /**
     * Set the value of pusherObject
     *
     * @return  self
     */
    public function setPusherObject($pusherObject)
    {
        $this->pusherObject = $pusherObject;

        return $this;
    }
}
