<?php

declare(strict_types=1);

namespace WardTest\Entity;

use PHPUnit\Framework\TestCase;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;
use General\Entity\Gender;

class WardTest extends TestCase
{
    public function testGetAge(): void
    {
        $ward = new Ward();
        
        $dob = new \DateTime('-10 years');
        $ward->setDateOfBirth($dob);

        $this->assertSame(10, $ward->getAge());
    }

    public function testExpireDateAndExpireHours(): void
    {
        $ward = new Ward();

        $this->assertNull($ward->getExpireDate());
        $this->assertNull($ward->getExpireHours());

        // Set expiry date to 48 hours in the future
        $futureDate = (new \DateTime())->modify('+48 hours');
        $ward->setExpireDate($futureDate);

        $this->assertSame($futureDate, $ward->getExpireDate());
        $this->assertEqualsWithDelta(48, $ward->getExpireHours(), 1);
    }

    public function testWardStatusId(): void
    {
        $ward = new Ward();
        $wardStatus = new WardStatus();
        $wardStatus->setId(WardStatus::STATUS_ACTIVE_ID);
        $wardStatus->setStatus(WardStatus::STATUS_ACTIVE);

        $ward->setStatus($wardStatus);

        $this->assertSame(1, $ward->getStatus()->getId());
        $this->assertSame('active', $ward->getStatus()->getStatus());
    }

    public function testGender(): void
    {
        $ward = new Ward();
        $this->assertNull($ward->getGender());

        $gender = new Gender();
        $gender->setGender('Female');
        $ward->setGender($gender);

        $this->assertSame($gender, $ward->getGender());
        $this->assertSame('Female', $ward->getGender()->getGender());
    }
}
