<?php

namespace Subscription\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;
use Subscription\Entity\SubscriptionType;
use Subscription\Entity\Invoice;
use Ramsey\Uuid\Uuid;

class SubscriptionService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TokenDecryptionService
     */
    private $tokenService;

    /**
     * @var string
     */
    private $paystackSecretKey;

    /**
     * SubscriptionService constructor.
     *
     * @param EntityManager $entityManager
     * @param TokenDecryptionService $tokenService
     * @param string|null $paystackSecretKey
     */
    public function __construct(
        EntityManager $entityManager,
        TokenDecryptionService $tokenService,
        ?string $paystackSecretKey = null
    ) {
        $this->entityManager = $entityManager;
        $this->tokenService = $tokenService;
        $this->paystackSecretKey = $paystackSecretKey
            ?: getenv('PAYSTACK_SECRET_KEY')
            ?: 'sk_test_paystack_dyxi_demo_secret_key';
    }

    /**
     * Seed default subscription types if they do not exist.
     * Required:
     * 1. 39,990 NGN or $39.95 USD monthly
     * 2. 110,999 NGN or $105.95 USD monthly
     */
    public function seedSubscriptionTypes(): array
    {
        $repo = $this->entityManager->getRepository(SubscriptionType::class);

        $plans = [
            [
                'name'            => 'Monthly Standard Subscription',
                'code'            => 'monthly_standard',
                'amount_ngn'      => 39990.00,
                'amount_usd'      => 39.95,
                'interval_months' => 1,
                'description'     => 'Standard monthly subscription plan for ward access and features.',
            ],
            [
                'name'            => 'Monthly Premium Subscription',
                'code'            => 'monthly_premium',
                'amount_ngn'      => 110999.00,
                'amount_usd'      => 105.95,
                'interval_months' => 1,
                'description'     => 'Premium monthly subscription plan with full ward suite & consultant access.',
            ],
        ];

        $createdOrUpdated = [];
        foreach ($plans as $p) {
            $entity = $repo->findOneBy(['code' => $p['code']]);
            if (! $entity) {
                $entity = new SubscriptionType();
                $entity->setCode($p['code']);
            }
            $entity->setName($p['name'])
                   ->setAmountNgn($p['amount_ngn'])
                   ->setAmountUsd($p['amount_usd'])
                   ->setIntervalMonths($p['interval_months'])
                   ->setDescription($p['description']);

            $this->entityManager->persist($entity);
            $createdOrUpdated[] = $entity;
        }

        $this->entityManager->flush();
        return $createdOrUpdated;
    }

    /**
     * Get all active subscription types.
     *
     * @return SubscriptionType[]
     */
    public function getSubscriptionTypes(): array
    {
        $this->seedSubscriptionTypes();
        return $this->entityManager->getRepository(SubscriptionType::class)->findAll();
    }

    /**
     * Decrypt token and validate User and Ward relationship.
     *
     * @param string $token Encrypted token
     * @return array Decrypted details, user entity, ward entity, and subscription types
     * @throws \Exception
     */
    public function processTokenAndValidate(string $token): array
    {
        $decrypted = $this->tokenService->decryptToken($token);

        $userId = $decrypted['user_id'];
        $wardId = $decrypted['ward_id'];

        if (! $userId || ! $wardId) {
            throw new \Exception("Decrypted token payload missing userId or wardId.");
        }

        $validated = $this->validateUserAndWard($userId, $wardId);

        $subscriptionTypes = $this->getSubscriptionTypes();
        $typesArray = array_map(function (SubscriptionType $type) {
            return $type->toArray();
        }, $subscriptionTypes);

        return [
            'decrypted_token'    => $decrypted,
            'user'               => [
                'id'       => $validated['user']->getId(),
                'uuid'     => $validated['user']->getUuid(),
                'email'    => $validated['user']->getEmail(),
                'fullname' => $validated['user']->getFullname(),
            ],
            'ward'               => [
                'id'          => $validated['ward']->getId(),
                'uuid'        => $validated['ward']->getUuid(),
                'fullname'    => $validated['ward']->getFullname(),
                'expire_date' => $validated['ward']->getExpireDate() ? $validated['ward']->getExpireDate()->format('Y-m-d H:i:s') : null,
                'is_expired'  => ($validated['ward']->getExpireDate() && $validated['ward']->getExpireDate() < new \DateTime()),
            ],
            'subscription_types' => $typesArray,
        ];
    }

    /**
     * Validate user & ward exist and have relationship.
     *
     * @param string|int $userIdOrUuid
     * @param string|int $wardIdOrUuid
     * @return array ['user' => User, 'ward' => Ward]
     * @throws \Exception
     */
    public function validateUserAndWard($userIdOrUuid, $wardIdOrUuid): array
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $wardRepo = $this->entityManager->getRepository(Ward::class);

        // Find user
        $user = is_numeric($userIdOrUuid)
            ? $userRepo->find((int) $userIdOrUuid)
            : $userRepo->findOneBy(['uuid' => $userIdOrUuid]);

        if (! $user) {
            throw new \Exception("User with identifier '{$userIdOrUuid}' does not exist.");
        }

        // Find ward
        $ward = is_numeric($wardIdOrUuid)
            ? $wardRepo->find((int) $wardIdOrUuid)
            : $wardRepo->findOneBy(['uuid' => $wardIdOrUuid]);

        if (! $ward) {
            throw new \Exception("Ward with identifier '{$wardIdOrUuid}' does not exist.");
        }

        // Check relationship (ward belongs to user)
        if (! $ward->getUser() || $ward->getUser()->getId() !== $user->getId()) {
            throw new \Exception("User (ID: {$user->getId()}) does not have ownership or relationship with Ward (ID: {$ward->getId()}).");
        }

        return [
            'user' => $user,
            'ward' => $ward,
        ];
    }

    /**
     * Generate an invoice with status 'pending' when subscription type is selected.
     *
     * @param string|int $userId
     * @param string|int $wardId
     * @param int|string $subscriptionTypeIdOrCode
     * @param string $currency 'NGN' or 'USD'
     * @return Invoice
     * @throws \Exception
     */
    public function generatePendingInvoice($userId, $wardId, $subscriptionTypeIdOrCode, string $currency = 'NGN'): Invoice
    {
        $validated = $this->validateUserAndWard($userId, $wardId);
        $user = $validated['user'];
        $ward = $validated['ward'];

        // Find subscription type
        $subTypeRepo = $this->entityManager->getRepository(SubscriptionType::class);
        $subscriptionType = is_numeric($subscriptionTypeIdOrCode)
            ? $subTypeRepo->find((int) $subscriptionTypeIdOrCode)
            : $subTypeRepo->findOneBy(['code' => $subscriptionTypeIdOrCode]);

        if (! $subscriptionType) {
            throw new \Exception("Subscription type not found.");
        }

        $currency = strtoupper($currency) === 'USD' ? 'USD' : 'NGN';
        $amount = ($currency === 'USD') ? $subscriptionType->getAmountUsd() : $subscriptionType->getAmountNgn();

        // Generate UUID and unique invoice number
        $invoiceUuid = Uuid::uuid4()->toString();
        $invoiceNum  = 'INV-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

        $invoice = new Invoice();
        $invoice->setUuid($invoiceUuid)
                ->setInvoiceNumber($invoiceNum)
                ->setUser($user)
                ->setWard($ward)
                ->setSubscriptionType($subscriptionType)
                ->setAmount($amount)
                ->setCurrency($currency)
                ->setStatus(Invoice::STATUS_PENDING);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    /**
     * Initialize payment with Nigeria Paystack payment gateway.
     *
     * @param string $invoiceUuidOrNumber
     * @param string|null $callbackUrl
     * @return array Paystack initialization payload & checkout url
     * @throws \Exception
     */
    public function initializePaystackPayment(string $invoiceUuidOrNumber, ?string $callbackUrl = null): array
    {
        $invoiceRepo = $this->entityManager->getRepository(Invoice::class);
        $invoice = $invoiceRepo->findOneBy(['uuid' => $invoiceUuidOrNumber])
            ?: $invoiceRepo->findOneBy(['invoice_number' => $invoiceUuidOrNumber]);

        if (! $invoice) {
            throw new \Exception("Invoice not found.");
        }

        if ($invoice->getStatus() === Invoice::STATUS_PAID) {
            throw new \Exception("Invoice is already paid.");
        }

        $reference = 'PAY-' . $invoice->getInvoiceNumber() . '-' . time();
        $invoice->setPaystackReference($reference);
        $this->entityManager->flush();

        // Amount in Kobo for NGN (1 NGN = 100 Kobo)
        $amountInKobo = (int) round($invoice->getAmount() * 100);

        $payload = [
            'email'        => $invoice->getUser()->getEmail(),
            'amount'       => $amountInKobo,
            'currency'     => $invoice->getCurrency(),
            'reference'    => $reference,
            'callback_url' => $callbackUrl ?: getenv('APP_URL') . '/api/subscription/paystack/callback',
            'metadata'     => [
                'invoice_id'     => $invoice->getId(),
                'invoice_uuid'   => $invoice->getUuid(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'ward_id'        => $invoice->getWard()->getId(),
                'user_id'        => $invoice->getUser()->getId(),
            ],
        ];

        // Call Paystack API if key is set, or return structure for Paystack Inline JS / standard redirect
        $authorizationUrl = null;
        $paystackResponse = null;

        if (! empty($this->paystackSecretKey) && strpos($this->paystackSecretKey, 'sk_') === 0) {
            $ch = curl_init('https://api.paystack.co/transaction/initialize');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->paystackSecretKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $result = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (! $err && $result) {
                $paystackResponse = json_decode($result, true);
                if (isset($paystackResponse['data']['authorization_url'])) {
                    $authorizationUrl = $paystackResponse['data']['authorization_url'];
                }
            }
        }

        return [
            'status'            => true,
            'message'           => 'Paystack transaction initialized.',
            'reference'         => $reference,
            'invoice'           => $invoice->toArray(),
            'authorization_url' => $authorizationUrl,
            'paystack_payload'  => $payload,
            'paystack_public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_paystack_dyxi_demo_public_key',
        ];
    }

    /**
     * Verify payment status and update Ward expiration date upon success.
     *
     * @param string $reference Paystack transaction reference or Invoice Number
     * @param bool $forceSuccess For testing/simulation mode
     * @return array
     * @throws \Exception
     */
    public function verifyAndFulfillPayment(string $reference, bool $forceSuccess = false): array
    {
        $invoiceRepo = $this->entityManager->getRepository(Invoice::class);
        $invoice = $invoiceRepo->findOneBy(['paystackReference' => $reference])
            ?: $invoiceRepo->findOneBy(['invoiceNumber' => $reference])
            ?: $invoiceRepo->findOneBy(['uuid' => $reference]);

        if (! $invoice) {
            throw new \Exception("Invoice not found for reference '{$reference}'.");
        }

        $isSuccessful = $forceSuccess;

        // If not forceSuccess, call Paystack API to verify reference
        if (! $isSuccessful && ! empty($this->paystackSecretKey) && strpos($this->paystackSecretKey, 'sk_') === 0) {
            $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->paystackSecretKey,
                ],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result) {
                $resData = json_decode($result, true);
                if (isset($resData['status']) && $resData['status'] === true && ($resData['data']['status'] ?? '') === 'success') {
                    $isSuccessful = true;
                }
            }
        }

        if (! $isSuccessful && ! $forceSuccess) {
            throw new \Exception("Payment verification failed or pending with Paystack.");
        }

        // 1. Mark Invoice as paid
        $invoice->setStatus(Invoice::STATUS_PAID);
        $invoice->setPaidOn(new \DateTime());

        // 2. Extend target Ward expiration date
        $ward = $invoice->getWard();
        $monthsToAdd = $invoice->getSubscriptionType() ? $invoice->getSubscriptionType()->getIntervalMonths() : 1;

        $currentExpire = $ward->getExpireDate();
        $now = new \DateTime();

        // If ward expireDate is in the future, extend from current expireDate; otherwise set from now
        if ($currentExpire && $currentExpire > $now) {
            $newExpire = (clone $currentExpire)->modify("+{$monthsToAdd} month");
        } else {
            $newExpire = (new \DateTime())->modify("+{$monthsToAdd} month");
        }

        $ward->setExpireDate($newExpire);

        // Update ward status to active if status exists
        $statusRepo = $this->entityManager->getRepository(WardStatus::class);
        $activeStatus = $statusRepo->findOneBy(['status' => WardStatus::STATUS_ACTIVE]);
        if ($activeStatus) {
            $ward->setStatus($activeStatus);
        }

        $this->entityManager->flush();

        return [
            'status'           => true,
            'message'          => 'Payment verified successfully and Ward expiration extended.',
            'invoice'          => $invoice->toArray(),
            'ward'             => [
                'id'              => $ward->getId(),
                'fullname'        => $ward->getFullname(),
                'old_expire_date' => $currentExpire ? $currentExpire->format('Y-m-d H:i:s') : null,
                'new_expire_date' => $newExpire->format('Y-m-d H:i:s'),
                'expire_hours'    => $ward->getExpireHours(),
            ],
        ];
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getTokenService(): TokenDecryptionService
    {
        return $this->tokenService;
    }
}
