<?php

namespace Subscription\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Subscription\Service\SubscriptionService;

class SubscriptionController extends AbstractActionController
{
    /**
     * @var SubscriptionService
     */
    private $subscriptionService;

    /**
     * SubscriptionController constructor.
     *
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Public web route: /subscribe[/:token]
     * Renders modern Web App UI for token decryption, plan selection, pending invoice, and Paystack checkout.
     */
    public function indexAction()
    {
        $token = $this->params()->fromRoute('token')
            ?: $this->params()->fromQuery('token')
            ?: null;

        $decryptedData = null;
        $error = null;

        if (! $token) {
            $error = 'Subscription access token is required. Please provide a valid authorization link to proceed.';
        } else {
            try {
                $decryptedData = $this->subscriptionService->processTokenAndValidate($token);
            } catch (\Exception $e) {
                $error = 'Invalid or expired subscription access token: ' . $e->getMessage();
            }
        }

        $subscriptionTypes = $this->subscriptionService->getSubscriptionTypes();
        $typesArray = array_map(function ($type) {
            return $type->toArray();
        }, $subscriptionTypes);

        $viewModel = new ViewModel([
            'token'              => $token,
            'decryptedData'      => $decryptedData,
            'subscriptionTypes'  => $typesArray,
            'error'              => $error,
            'paystackPublicKey'  => getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_e373276edc8ab4e606cbe28192fbbe8c9bf926ba',
        ]);
        $viewModel->setTemplate('subscription/subscription/index');
        $viewModel->setTerminal(true);

        return $viewModel;
    }

    /**
     * API / Web Route: Generate real seed data (User, Ward, Invoice, encrypted token URL)
     * Route: /api/subscription/seed-test
     */
    public function seedTestAction()
    {
        try {
            $result = $this->subscriptionService->seedTestData();

            if ($this->params()->fromQuery('redirect') == '1') {
                return $this->redirect()->toUrl($result['subscribe_url']);
            }

            return new JsonModel($result);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Decrypt token and validate User & Ward relationship
     * Endpoint: POST /api/subscription/decrypt
     */
    public function decryptAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();
            $token = $data['token'] ?? $this->params()->fromQuery('token') ?? null;

            if (! $token) {
                return new JsonModel([
                    'status'  => false,
                    'error'   => 'Token parameter is required.',
                ]);
            }

            $result = $this->subscriptionService->processTokenAndValidate($token);

            return new JsonModel([
                'status' => true,
                'data'   => $result,
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Generate Pending Invoice once user selects subscription type
     * Endpoint: POST /api/subscription/create-invoice
     */
    public function createInvoiceAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();

            $userId = $data['user_id'] ?? $data['userId'] ?? null;
            $wardId = $data['ward_id'] ?? $data['wardId'] ?? null;
            $subscriptionTypeCode = $data['subscription_type_code'] ?? $data['subscription_type_id'] ?? null;
            $currency = $data['currency'] ?? 'NGN';

            if (! $userId || ! $wardId || ! $subscriptionTypeCode) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'user_id, ward_id, and subscription_type_code are required.',
                ]);
            }

            $invoice = $this->subscriptionService->generatePendingInvoice(
                $userId,
                $wardId,
                $subscriptionTypeCode,
                $currency
            );

            return new JsonModel([
                'status'  => true,
                'message' => 'Pending invoice generated successfully.',
                'invoice' => $invoice->toArray(),
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Revoke previous pending invoice and generate new invoice for changed service
     * Endpoint: POST /api/subscription/change-plan
     */
    public function changePlanAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();

            $userId = $data['user_id'] ?? $data['userId'] ?? 1;
            $wardId = $data['ward_id'] ?? $data['wardId'] ?? 1;
            $subscriptionTypeCode = $data['subscription_type_code'] ?? $data['subscription_type_id'] ?? $data['service'] ?? null;
            $invoiceUuid = $data['invoice_uuid'] ?? $data['invoice_number'] ?? null;
            $currency = $data['currency'] ?? 'NGN';

            if (! $subscriptionTypeCode) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'subscription_type_code is required to change service.',
                ]);
            }

            $result = $this->subscriptionService->revokeAndChangePendingInvoice(
                $userId,
                $wardId,
                $subscriptionTypeCode,
                $invoiceUuid,
                $currency
            );

            return new JsonModel($result);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Update pending invoice currency and value in database
     * Endpoint: POST /api/subscription/update-currency
     */
    public function updateCurrencyAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();

            $userId = $data['user_id'] ?? $data['userId'] ?? null;
            $wardId = $data['ward_id'] ?? $data['wardId'] ?? null;
            $currency = $data['currency'] ?? 'NGN';
            $invoiceUuid = $data['invoice_uuid'] ?? $data['invoice_number'] ?? null;

            if (! $userId || ! $wardId) {
                if (! empty($data['token'])) {
                    $decrypted = $this->subscriptionService->getTokenService()->decryptToken($data['token']);
                    $userId = $userId ?: ($decrypted['user_id'] ?? $decrypted['userId'] ?? null);
                    $wardId = $wardId ?: ($decrypted['ward_id'] ?? $decrypted['wardId'] ?? null);
                }
            }

            if (! $userId || ! $wardId) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'user_id and ward_id are required to update invoice currency.',
                ]);
            }

            $result = $this->subscriptionService->updateInvoiceCurrency(
                $userId,
                $wardId,
                $currency,
                $invoiceUuid
            );

            return new JsonModel($result);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Initialize payment with Nigeria Paystack network
     * Endpoint: POST /api/subscription/paystack-initialize
     */
    public function paystackInitializeAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();

            $invoiceUuid = $data['invoice_uuid'] ?? $data['invoice_number'] ?? null;
            $callbackUrl = $data['callback_url'] ?? null;

            if (! $invoiceUuid) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'invoice_uuid parameter is required.',
                ]);
            }

            $response = $this->subscriptionService->initializePaystackPayment($invoiceUuid, $callbackUrl);

            return new JsonModel($response);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Verify Paystack Payment & Update Ward expiration date
     * Endpoint: POST /api/subscription/paystack-verify
     */
    public function paystackVerifyAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();
            $reference = $data['reference'] ?? $this->params()->fromQuery('reference') ?? null;
            $forceSuccess = ! empty($data['simulate_success']) || $this->params()->fromQuery('simulate_success') == '1';

            if (! $reference) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'Payment reference parameter is required.',
                ]);
            }

            $paymentMethod = $data['payment_method'] ?? $data['paymentMethod'] ?? 'paystack';
            $extraDetails = $data['payment_details'] ?? $data['details'] ?? [];

            $result = $this->subscriptionService->verifyAndFulfillPayment($reference, $forceSuccess, $paymentMethod, $extraDetails);

            return new JsonModel($result);
        } catch (\Exception $e) {
            if (! empty($reference)) {
                try {
                    $em = $this->subscriptionService->getEntityManager();
                    $invoiceRepo = $em->getRepository(\Subscription\Entity\Invoice::class);
                    $invoice = $invoiceRepo->findOneBy(['paystackReference' => $reference])
                        ?: $invoiceRepo->findOneBy(['invoiceNumber' => $reference])
                        ?: $invoiceRepo->findOneBy(['referenceCode' => $reference])
                        ?: $invoiceRepo->findOneBy(['uuid' => $reference]);

                    if ($invoice) {
                        $this->subscriptionService->logFailedTransaction($invoice, $reference, $e->getMessage(), $paymentMethod ?? 'paystack');
                    }
                } catch (\Throwable $txErr) {}
            }

            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Webhook callback for Paystack network notifications
     * Endpoint: POST /api/subscription/paystack-webhook
     */
    public function paystackWebhookAction()
    {
        $input = file_get_contents('php://input');
        $event = json_decode($input, true);

        if ($event && isset($event['event']) && $event['event'] === 'charge.success') {
            $reference = $event['data']['reference'] ?? null;
            $webhookData = $event['data'] ?? [];
            $channel = $event['data']['channel'] ?? 'paystack';

            if ($reference) {
                try {
                    $this->subscriptionService->verifyAndFulfillPayment($reference, true, $channel, $webhookData);
                    return new JsonModel(['status' => 'success']);
                } catch (\Exception $e) {
                    return new JsonModel(['status' => 'error', 'message' => $e->getMessage()]);
                }
            }
        }

        return new JsonModel(['status' => 'ignored']);
    }

    /**
     * API: Utility route to generate encrypted token for testing
     * Endpoint: POST /api/subscription/generate-token
     */
    public function generateTokenAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();
            $userId = $data['user_id'] ?? $data['userId'] ?? 1;
            $wardId = $data['ward_id'] ?? $data['wardId'] ?? 1;

            $tokenService = $this->subscriptionService->getTokenService();
            $token = $tokenService->encryptToken([
                'userId' => $userId,
                'wardId' => $wardId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return new JsonModel([
                'status'          => true,
                'user_id'         => $userId,
                'ward_id'         => $wardId,
                'encrypted_token' => $token,
                'subscribe_url'   => '/subscribe/' . $token,
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Send official DYXI payment receipt email via Postmark API
     * Endpoint: POST /api/subscription/send-receipt
     */
    public function sendReceiptAction()
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true) ?: $this->params()->fromPost();
            $invoiceUuid = $data['invoice_uuid'] ?? $data['invoice_number'] ?? null;
            $email = $data['email'] ?? null;

            if (! $invoiceUuid) {
                return new JsonModel([
                    'status' => false,
                    'error'  => 'invoice_uuid parameter is required.',
                ]);
            }

            $result = $this->subscriptionService->sendPostmarkReceiptEmail($invoiceUuid, $email);

            return new JsonModel($result);
        } catch (\Exception $e) {
            return new JsonModel([
                'status' => false,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
