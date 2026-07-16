<?php

namespace Authentication\Service;

use DateTimeImmutable;
use Lcobucci\Clock\Clock;

class JWTClock extends Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
