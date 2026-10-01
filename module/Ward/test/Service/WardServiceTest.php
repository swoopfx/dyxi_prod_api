<?php

declare(strict_types=1);

namespace WardTest\Service;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Ward\Service\WardService;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;
use Authentication\Entity\User;
use General\Entity\Gender;
use Ramsey\Uuid\Uuid;

class WardServiceTest extends TestCase
{
    private $entityManager;
    private $userRepo;
    private $wardRepo;
    private $statusRepo;
    private $genderRepo;
    private $invoiceRepo;
    private $subTypeRepo;
    private $wardService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->userRepo = $this->createMock(EntityRepository::class);
        $this->wardRepo = $this->createMock(EntityRepository::class);
        $this->statusRepo = $this->createMock(EntityRepository::class);
        $this->genderRepo = $this->createMock(EntityRepository::class);
        $this->invoiceRepo = $this->createMock(EntityRepository::class);
        $this->subTypeRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')->willReturnCallback(function ($entityClass) {
            if ($entityClass === User::class) {
                return $this->userRepo;
            }
            if ($entityClass === Ward::class) {
                return $this->wardRepo;
            }
            if ($entityClass === WardStatus::class) {
                return $this->statusRepo;
            }
            if ($entityClass === Gender::class) {
                return $this->genderRepo;
            }
            if ($entityClass === \Subscription\Entity\Invoice::class) {
                return $this->invoiceRepo;
            }
            if ($entityClass === \Subscription\Entity\SubscriptionType::class) {
                return $this->subTypeRepo;
            }
            return null;
        });

        $this->wardService = new WardService($this->entityManager);
    }

    public function testRegisterSuccessful(): void
    {
        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => 'user-uuid-1234'])
            ->willReturn($user);

        $this->wardRepo->method('findBy')->with(['user' => $user])->willReturn([]);
        $this->wardRepo->method('findOneBy')->willReturn(null);

        $standardPlan = new \Subscription\Entity\SubscriptionType();
        $standardPlan->setMaxChild(1);
        $this->subTypeRepo->method('findOneBy')->with(['code' => 'monthly_standard'])->willReturn($standardPlan);
        $this->invoiceRepo->method('findBy')->willReturn([]);

        $defaultStatus = new WardStatus();
        $defaultStatus->setStatus(WardStatus::STATUS_ACTIVE);
        $this->statusRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['status' => WardStatus::STATUS_ACTIVE])
            ->willReturn($defaultStatus);

        $femaleGender = new Gender();
        $femaleGender->setGender('Female');
        $this->genderRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['gender' => 'Female'])
            ->willReturn($femaleGender);

        $postData = [
            'fullname' => 'Jane Doe',
            'date_of_birth' => '2018-05-10'
        ];

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $ward = $this->wardService->register($postData, $identity);

        $this->assertInstanceOf(Ward::class, $ward);
        $this->assertSame('Jane Doe', $ward->getFullname());
        $this->assertSame('2018-05-10', $ward->getDateOfBirth()->format('Y-m-d'));
        $this->assertTrue(Uuid::isValid($ward->getUuid()));
        $this->assertSame($defaultStatus, $ward->getStatus());
        $this->assertSame($femaleGender, $ward->getGender());

        // Check expireDate is set to 1 day before present date
        $expectedExpireDate = (new \DateTime())->modify('-1 day');
        $this->assertEqualsWithDelta(
            $expectedExpireDate->getTimestamp(),
            $ward->getExpireDate()->getTimestamp(),
            2
        );
    }

    public function testRegisterThrowsExceptionWhenMaxChildLimitReached(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum child limit reached for your subscription plan. Limit is 1 child(ren).');

        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->wardRepo->method('findBy')->with(['user' => $user])->willReturn([new Ward()]);

        $standardPlan = new \Subscription\Entity\SubscriptionType();
        $standardPlan->setMaxChild(1);
        $this->subTypeRepo->method('findOneBy')->with(['code' => 'monthly_standard'])->willReturn($standardPlan);
        $this->invoiceRepo->method('findBy')->willReturn([]);

        $this->wardService->register([
            'fullname' => 'Second Ward',
            'date_of_birth' => '2019-01-01'
        ], $identity);
    }

    public function testRegisterThrowsExceptionWhenDateOfBirthMissing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Date of birth is required.');

        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->wardRepo->method('findBy')->with(['user' => $user])->willReturn([]);

        $this->wardService->register([
            'fullname' => 'No DOB Ward'
        ], $identity);
    }

    public function testEditWardSuccess(): void
    {
        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => 'user-uuid-1234'])
            ->willReturn($user);

        $existingWard = new Ward();
        $existingWard->setFullname('Old Name');
        $existingWard->setDateOfBirth(new \DateTime('2015-01-01'));

        $this->wardRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 1, 'user' => $user])
            ->willReturn($existingWard);

        $maleGender = new Gender();
        $maleGender->setGender('Male');
        $this->genderRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['gender' => 'Male'])
            ->willReturn($maleGender);

        $activeStatus = new WardStatus();
        $activeStatus->setStatus('active');
        $this->statusRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['status' => 'active'])
            ->willReturn($activeStatus);

        $this->entityManager->expects($this->once())->method('flush');

        $editData = [
            'id' => 1,
            'fullname' => 'New Name',
            'date_of_birth' => '2016-02-02',
            'gender' => 'Male',
            'status' => 'active'
        ];

        $updatedWard = $this->wardService->editWard($editData, $identity);

        $this->assertSame('New Name', $updatedWard->getFullname());
        $this->assertSame('2016-02-02', $updatedWard->getDateOfBirth()->format('Y-m-d'));
        $this->assertSame($maleGender, $updatedWard->getGender());
        $this->assertSame($activeStatus, $updatedWard->getStatus());
    }

    public function testEditWardThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ward not found or you do not have permission to edit it.');

        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->wardRepo->method('findOneBy')->willReturn(null);

        $this->wardService->editWard(['id' => 999, 'fullname' => 'Test'], $identity);
    }

    public function testEditWardThrowsExceptionWhenInvalidDob(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid date of birth format. Use YYYY-MM-DD.');

        $identity = ['uuid' => 'user-uuid-1234'];
        $user = new User();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $ward = new Ward();
        $this->wardRepo->method('findOneBy')->willReturn($ward);

        $this->wardService->editWard(['id' => 1, 'date_of_birth' => 'invalid-date'], $identity);
    }
}
