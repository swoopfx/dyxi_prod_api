<?php

declare(strict_types=1);

namespace WardTest\Entity;

use PHPUnit\Framework\TestCase;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;

class WardStatusTest extends TestCase
{
    public function testPresetConstants(): void
    {
        $this->assertSame('active', WardStatus::STATUS_ACTIVE);
        $this->assertSame('suspended', WardStatus::STATUS_SUSPENDED);
        $this->assertSame('pending', WardStatus::STATUS_PENDING);

        $this->assertSame(1, WardStatus::STATUS_ACTIVE_ID);
        $this->assertSame(2, WardStatus::STATUS_SUSPENDED_ID);
        $this->assertSame(3, WardStatus::STATUS_PENDING_ID);

        $this->assertContains('active', WardStatus::PRESET_STATUSES);
        $this->assertContains('suspended', WardStatus::PRESET_STATUSES);
        $this->assertContains('pending', WardStatus::PRESET_STATUSES);
    }

    public function testGettersAndSetters(): void
    {
        $wardStatus = new WardStatus();
        $wardStatus->setId(1);
        $wardStatus->setStatus(WardStatus::STATUS_ACTIVE);

        $this->assertSame(1, $wardStatus->getId());
        $this->assertSame('active', $wardStatus->getStatus());
    }

    public function testWardRelationship(): void
    {
        $ward = new Ward();
        $wardStatus = new WardStatus();
        $wardStatus->setId(1);
        $wardStatus->setStatus(WardStatus::STATUS_ACTIVE);

        $ward->setStatus($wardStatus);

        $this->assertSame($wardStatus, $ward->getStatus());
        $this->assertSame('active', $ward->getStatus()->getStatus());
    }
}
