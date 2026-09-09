<?php

namespace Consultant\Service;

use Doctrine\ORM\EntityManager;
use Consultant\Entity\Consultant;
use Consultant\Entity\ConsultantCategory;
use Consultant\Entity\ConsultantBooking;
use Authentication\Entity\User;
use Authentication\Service\AuthMailtrapService;
use General\Service\GeneralService;
use Ramsey\Uuid\Uuid;

class ConsultantService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AuthMailtrapService|null
     */
    private $authMailtrapService;

    const ALLOWED_CATEGORIES = ['Doctor', 'Consultant', 'Psychologist'];

    /**
     * ConsultantService constructor.
     *
     * @param EntityManager $entityManager
     * @param AuthMailtrapService|null $authMailtrapService
     */
    public function __construct(EntityManager $entityManager, ?AuthMailtrapService $authMailtrapService = null)
    {
        $this->entityManager = $entityManager;
        $this->authMailtrapService = $authMailtrapService;
    }

    // ==========================================
    // CONSULTANT CATEGORY CRUD & RESOLUTION
    // ==========================================

    /**
     * Create a new ConsultantCategory entity.
     *
     * @param array $data
     * @return ConsultantCategory
     * @throws \Exception
     */
    public function createCategory(array $data): ConsultantCategory
    {
        if (empty($data['name'])) {
            throw new \Exception("Category name is required.");
        }

        $existing = $this->entityManager->getRepository(ConsultantCategory::class)->findOneBy(['name' => trim($data['name'])]);
        if ($existing) {
            throw new \Exception("A consultant category with this name already exists.");
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $category = new ConsultantCategory();
        $category->setUuid($uuid)
                 ->setName(trim($data['name']))
                 ->setDescription($data['description'] ?? null);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * List all Consultant categories.
     *
     * @return array
     */
    public function listCategories(): array
    {
        return $this->entityManager->getRepository(ConsultantCategory::class)->findBy([], ['name' => 'ASC']);
    }

    /**
     * Get ConsultantCategory by ID, UUID, or Name.
     *
     * @param string|int $idOrUuidOrName
     * @return ConsultantCategory|null
     */
    public function getCategoryInfo($idOrUuidOrName): ?ConsultantCategory
    {
        if (empty($idOrUuidOrName)) {
            return null;
        }

        $repo = $this->entityManager->getRepository(ConsultantCategory::class);
        $category = null;

        if (is_numeric($idOrUuidOrName)) {
            $category = $repo->find((int) $idOrUuidOrName);
        }
        if (!$category) {
            $category = $repo->findOneBy(['uuid' => $idOrUuidOrName]);
        }
        if (!$category) {
            $category = $repo->findOneBy(['name' => $idOrUuidOrName]);
        }
        if (!$category) {
            // Case-insensitive name match
            $categories = $repo->findAll();
            foreach ($categories as $cat) {
                if (strcasecmp($cat->getName(), (string) $idOrUuidOrName) === 0) {
                    $category = $cat;
                    break;
                }
            }
        }

        return $category;
    }

    // ==========================================
    // CONSULTANT CRUD
    // ==========================================

    /**
     * Create / Register a new Consultant profile.
     *
     * @param array $data Input parameters
     * @return Consultant
     * @throws \Exception
     */
    public function createConsultant(array $data): Consultant
    {
        if (empty($data['fullname'])) {
            throw new \Exception("Consultant fullname is required.");
        }

        if (empty($data['email'])) {
            throw new \Exception("Consultant email is required.");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email format.");
        }

        // Check unique email
        $existing = $this->entityManager->getRepository(Consultant::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            throw new \Exception("A consultant with this email address already exists.");
        }

        // Category resolution
        $categoryObj = null;
        $categoryInput = $data['category_id'] ?? $data['category_uuid'] ?? $data['category'] ?? null;
        if (!empty($categoryInput)) {
            if ($categoryInput instanceof ConsultantCategory) {
                $categoryObj = $categoryInput;
            } else {
                $categoryObj = $this->getCategoryInfo($categoryInput);
                if (!$categoryObj) {
                    throw new \Exception("ConsultantCategory not found for input: " . (is_array($categoryInput) ? json_encode($categoryInput) : $categoryInput));
                }
            }
        }

        // Resolve user if user_id or user_uuid is provided
        $user = null;
        if (!empty($data['user_id'])) {
            $userRepo = $this->entityManager->getRepository(User::class);
            if (is_numeric($data['user_id'])) {
                $user = $userRepo->find((int) $data['user_id']);
            } else {
                $user = $userRepo->findOneBy(['uuid' => $data['user_id']]);
            }

            if (!$user) {
                throw new \Exception("The associated user account could not be found.");
            }
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $speciality = $data['speciality'] ?? $data['specialization'] ?? null;
        $description = $data['description'] ?? $data['bio'] ?? null;
        $introduction = $data['introduction'] ?? null;
        $title = $data['title'] ?? null;

        $consultant = new Consultant();
        $consultant->setUuid($uuid)
                   ->setFullname($data['fullname'])
                   ->setEmail($data['email'])
                   ->setTitle($title)
                   ->setIntroduction($introduction)
                   ->setDescription($description)
                   ->setCategory($categoryObj)
                   ->setSpeciality($speciality)
                   ->setPhone($data['phone'] ?? null)
                   ->setUser($user);

        if (!empty($data['status'])) {
            $consultant->setStatus($data['status']);
        }

        $this->entityManager->persist($consultant);
        $this->entityManager->flush();

        return $consultant;
    }

    /**
     * Alias for createConsultant for backward compatibility
     */
    public function register(array $data): Consultant
    {
        return $this->createConsultant($data);
    }

    /**
     * Retrieve consultant profiles with optional search and filters.
     *
     * Filters supported:
     * - category / category_id / category_uuid: string|int
     * - speciality: string (or specialization)
     * - title: string
     * - search / q: string (free text search across fields)
     *
     * @param array $filters
     * @return array
     */
    public function listConsultants(array $filters = []): array
    {
        $qb = $this->entityManager->getRepository(Consultant::class)->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat');

        $categoryParam = $filters['category_id'] ?? $filters['category_uuid'] ?? $filters['category'] ?? null;
        if (!empty($categoryParam)) {
            if (is_numeric($categoryParam)) {
                $qb->andWhere('cat.id = :catParam')
                   ->setParameter('catParam', (int) $categoryParam);
            } elseif (Uuid::isValid((string) $categoryParam)) {
                $qb->andWhere('cat.uuid = :catParam')
                   ->setParameter('catParam', (string) $categoryParam);
            } else {
                $qb->andWhere('LOWER(cat.name) = LOWER(:catParam)')
                   ->setParameter('catParam', trim((string) $categoryParam));
            }
        }

        if (!empty($filters['speciality']) || !empty($filters['specialization'])) {
            $spec = trim($filters['speciality'] ?? $filters['specialization']);
            $qb->andWhere('(c.speciality LIKE :spec OR c.specialization LIKE :spec)')
               ->setParameter('spec', '%' . $spec . '%');
        }

        if (!empty($filters['title'])) {
            $qb->andWhere('c.title LIKE :title')
               ->setParameter('title', '%' . trim($filters['title']) . '%');
        }

        $search = trim($filters['search'] ?? $filters['q'] ?? '');
        if (!empty($search)) {
            $qb->andWhere(
                '(c.fullname LIKE :search OR c.title LIKE :search OR cat.name LIKE :search OR c.speciality LIKE :search OR c.introduction LIKE :search OR c.description LIKE :search)'
            )->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieve a specific consultant by ID, UUID, or email.
     *
     * @param string|int $idOrUuid
     * @return Consultant
     * @throws \Exception
     */
    public function getConsultantInfo($idOrUuid): Consultant
    {
        $repo = $this->entityManager->getRepository(Consultant::class);
        $consultant = null;

        if (is_numeric($idOrUuid)) {
            $consultant = $repo->find((int) $idOrUuid);
        }
        if (!$consultant) {
            $consultant = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$consultant) {
            $consultant = $repo->findOneBy(['email' => $idOrUuid]);
        }

        if (!$consultant) {
            throw new \Exception("Consultant not found.");
        }

        return $consultant;
    }

    // ==========================================
    // BOOKING & NOTIFICATIONS
    // ==========================================

    /**
     * Book a consultant appointment and notify both consultant and parent.
     *
     * @param array $data Input payload
     * @param User|null $parentUser Authenticated user (parent)
     * @return ConsultantBooking
     * @throws \Exception
     */
    public function bookConsultant(array $data, ?User $parentUser = null): ConsultantBooking
    {
        $consultantId = $data['consultant_id'] ?? $data['consultant_uuid'] ?? $data['id'] ?? null;
        if (empty($consultantId)) {
            throw new \Exception("Consultant identifier (consultant_id) is required.");
        }

        $consultant = $this->getConsultantInfo($consultantId);

        // Resolve Parent User
        $user = $parentUser;
        if (!$user && !empty($data['user_id'])) {
            $userRepo = $this->entityManager->getRepository(User::class);
            if (is_numeric($data['user_id'])) {
                $user = $userRepo->find((int) $data['user_id']);
            } else {
                $user = $userRepo->findOneBy(['uuid' => $data['user_id']]);
            }
        }

        if (!$user) {
            throw new \Exception("User (parent) making the booking is required.");
        }

        if (empty($data['appointment_date'])) {
            throw new \Exception("Appointment date is required.");
        }

        if (empty($data['preferred_time'])) {
            throw new \Exception("Preferred time is required.");
        }

        try {
            $appointmentDate = new \DateTime($data['appointment_date']);
        } catch (\Throwable $e) {
            throw new \Exception("Invalid appointment date format. Use YYYY-MM-DD.");
        }

        $uuid = Uuid::uuid4()->toString();

        $booking = new ConsultantBooking();
        $booking->setUuid($uuid)
                ->setConsultant($consultant)
                ->setUser($user)
                ->setAppointmentDate($appointmentDate)
                ->setPreferredTime(trim($data['preferred_time']))
                ->setReasonForVisit($data['reason_for_visit'] ?? null)
                ->setStatus(ConsultantBooking::STATUS_INITIATED);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        // Send mail notifications to consultant and parent
        $this->sendBookingNotifications($booking);

        return $booking;
    }

    /**
     * Send email notifications to Consultant and Parent regarding a booking.
     *
     * @param ConsultantBooking $booking
     * @return void
     */
    public function sendBookingNotifications(ConsultantBooking $booking): void
    {
        try {
            $consultant = $booking->getConsultant();
            $parent = $booking->getUser();

            $consultantEmail = $consultant->getEmail();
            $parentEmail = $parent->getEmail();

            $appointmentDateStr = $booking->getAppointmentDate()->format('Y-m-d');
            $preferredTime = $booking->getPreferredTime();
            $reason = $booking->getReasonForVisit() ?: 'N/A';

            $consultantName = ($consultant->getTitle() ? $consultant->getTitle() . ' ' : '') . $consultant->getFullname();
            $parentName = $parent->getFullname() ?: $parentEmail;
            $categoryName = $consultant->getCategory() ? $consultant->getCategory()->getName() : 'Consultant';

            // 1. Notification to Consultant
            $consultantMailBody = sprintf(
                "Hello %s,\n\nYou have a new consultant appointment booking on Dyxi Platform!\n\n" .
                "Booking Reference: %s\n" .
                "Parent Name: %s\n" .
                "Parent Email: %s\n" .
                "Appointment Date: %s\n" .
                "Preferred Time: %s\n" .
                "Reason for Visit: %s\n\n" .
                "Best regards,\nDyxi Platform Team",
                $consultantName,
                $booking->getUuid(),
                $parentName,
                $parentEmail,
                $appointmentDateStr,
                $preferredTime,
                $reason
            );

            // 2. Notification to Parent
            $parentMailBody = sprintf(
                "Hello %s,\n\nYour appointment with %s (%s) has been successfully booked on Dyxi Platform!\n\n" .
                "Booking Reference: %s\n" .
                "Consultant: %s\n" .
                "Category: %s\n" .
                "Speciality: %s\n" .
                "Appointment Date: %s\n" .
                "Preferred Time: %s\n" .
                "Reason for Visit: %s\n" .
                "Status: %s\n\n" .
                "Thank you for scheduling with us.\n\nBest regards,\nDyxi Platform Team",
                $parentName,
                $consultantName,
                $categoryName,
                $booking->getUuid(),
                $consultantName,
                $categoryName,
                $consultant->getSpeciality() ?: 'N/A',
                $appointmentDateStr,
                $preferredTime,
                $reason,
                ucfirst($booking->getStatus())
            );

            // Dispatch emails
            $this->dispatchEmail($consultantEmail, "New Appointment Booking - " . $parentName, $consultantMailBody);
            $this->dispatchEmail($parentEmail, "Appointment Booking Confirmation - " . $consultantName, $parentMailBody);

        } catch (\Throwable $th) {
            error_log("Consultant Booking Notification Mail Error: " . $th->getMessage());
        }
    }

    /**
     * Dispatch an email via AuthMailtrapService or PHP mail fallback.
     */
    private function dispatchEmail(string $to, string $subject, string $body): void
    {
        if (empty($to)) {
            return;
        }

        if ($this->authMailtrapService && method_exists($this->authMailtrapService, 'sendReceiptMail')) {
            try {
                $this->authMailtrapService->sendReceiptMail([
                    'to' => $to,
                    'logo' => '',
                    'fullname' => $to,
                    'desc' => $body,
                    'tRef' => $subject,
                    'amount' => '',
                    'total' => ''
                ]);
                return;
            } catch (\Throwable $e) {
                // fallback to native mail
            }
        }

        $headers = "From: " . GeneralService::EMAIL_NOTIFiER . "\r\n" .
                   "Reply-To: " . GeneralService::EMAIL_NOTIFiER . "\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        @mail($to, $subject, $body, $headers);
    }
}
