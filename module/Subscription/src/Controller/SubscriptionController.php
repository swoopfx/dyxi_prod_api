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

        if ($token) {
            try {
                $decryptedData = $this->subscriptionService->processTokenAndValidate($token);
            } catch (\Exception $e) {
                $error = $e->getMessage();
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
            'paystackPublicKey'  => getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_paystack_dyxi_demo_public_key',
        ]);
        $viewModel->setTemplate('subscription/subscription/index');

        return $viewModel;
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

            $result = $this->subscriptionService->verifyAndFulfillPayment($reference, $forceSuccess);

            return new JsonModel($result);
        } catch (\Exception $e) {
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
            if ($reference) {
                try {
                    $this->subscriptionService->verifyAndFulfillPayment($reference, true);
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
}
