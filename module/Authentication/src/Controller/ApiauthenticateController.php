<?php

namespace Authentication\Controller;

use Authentication\Entity\User;
use Authentication\Entity\UserRefreshToken;
use Authentication\Form\InputFilter\LoginInputFilter;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthenticationService;
use Authentication\Service\AuthMailtrapService;
use Authentication\Service\JWTIssuer;
use Authentication\Service\RegisterService;
use Doctrine\ORM\EntityManager;
use General\Service\Mailtrap\MailtrapService;
use General\Service\Postmark\AuthenticationEmailService;
use General\Service\GeneralService;
use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;
use Laminas\View\Model\JsonModel;

/**
 * @OA\Info(
 *     title="Dyxi API",
 *     version="1.0.0",
 *     description="API documentation for Dyxi API"
 * )
 * @OA\Server(
 *     url="http://localhost:8080",
 *     description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiauthenticateController extends AbstractActionController
{
    /**
     * Doctrine ORM EntityManager
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * GeneralSerive Class
     *
     * @var GeneralService
     */
    private $generalService;

    /**
     * Api Athentication Service
     *
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * Register Service
     *
     * @var RegisterService
     */
    private $registerService;

    /**
     * Undocumented variable
     *
     * @var MailtrapService
     */
    private $mailtrapService;

    /**
     * Undocumented variable
     *
     * @var AuthMailtrapService
     */
    private $authMailtrapService;

    /**
     * Undocumented variable
     *
     * @var JwtIssuer
     */
    private $jwtIssuer;

    /**
     * Undocumented variable
     *
     * @var AuthenticationEmailService
     */
    private $authPostmarkService;

    /**
     * Undocumented variable
     *
     * @var GoogleOAuthService
     */
    private $googleAuthService;

    /**
     * @var array
     */
    private $config;

    /**
     * This API is used to authenticate the user and retrieve a JWT bearer token.
     * @OA\Post(
     *     path="/auth/ipa/login",
     *     tags={"Authentication"},
     *     description="Authenticates client credentials (email or username, and password). On success, returns a JWT access token and user profile. If client is 'web', sets an HttpOnly cookie with the rotated refresh token; if client is 'mobile', returns the refresh token directly in the response body.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"username", "password", "user_agent", "user_ip"},
     *                     @OA\Property(property="username", type="string", example="ezekiel_a@yahoo.com", description="User's registered email address or username"),
     *                     @OA\Property(property="password", type="string", example="Oluwaseun1", description="User's plain text password"),
     *                     @OA\Property(property="user_agent", type="string", example="Mozilla/5.0...", description="User agent string of the client device"),
     *                     @OA\Property(property="user_ip", type="string", example="127.0.0.1", description="IP address of the client device"),
     *                     @OA\Property(property="remember_me", type="boolean", example=true, description="Optional. If true, extends refresh token and session cookie lifetime to 90 days."),
     *                     @OA\Property(property="client", type="string", enum={"web", "mobile"}, default="web", description="Client type: 'web' returns HttpOnly cookie; 'mobile' returns refresh_token in response JSON body.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful login, tokens and profile returned",
     *         @OA\Header(header="Set-Cookie", description="Session cookie containing the refresh token (only returned when client is web)", @OA\Schema(type="string", example="refresh_token=...")),
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="schema", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_in", type="integer", example=2592000, description="Access token lifetime in seconds"),
     *                     @OA\Property(property="token", type="string", example="eyJ0eXAi..."),
     *                     @OA\Property(property="luhn_token", type="string", example="26a14737...", description="Unique token ID value"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="fullname", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@doe.com"),
     *                         @OA\Property(property="role", type="string", example="Guardian"),
     *                         @OA\Property(property="username", type="string", example="john_doe"),
     *                         @OA\Property(property="uuid", type="string", example="d3b07384..."),
     *                         @OA\Property(property="wallet", type="integer", example=0),
     *                         @OA\Property(property="profile_pic", type="string", nullable=true, example="https://example.com/pic.jpg", description="URL to the user's profile picture")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. invalid credentials, unconfirmed email, or disabled account)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="AuthenticationError"),
     *                     @OA\Property(property="description", type="string", example="Invalid Credentials")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed (non-POST request)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @return JsonModel
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        // Converts it into a PHP object
        $postData = json_decode($json, true) ?: [];

        // Automatically supply user_agent and user_ip if not provided
        if (empty($postData['user_agent'])) {
            $userAgentHeader = $request->getHeader('User-Agent');
            $postData['user_agent'] = $userAgentHeader ? $userAgentHeader->getFieldValue() : 'Unknown';
        }
        if (empty($postData['user_ip'])) {
            $xForwardedFor = $request->getHeader('X-Forwarded-For');
            if ($xForwardedFor) {
                $postData['user_ip'] = explode(',', $xForwardedFor->getFieldValue())[0];
            } else {
                $postData['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            }
        }

        $inputFilter = $this->apiAuthService->getLoginInputFilter();
        $inputFilter->setValidationGroup([
            'username',
            'password',
            'user_agent',
            'user_ip'
        ]);
        $inputFilter->setData($postData);

        if (!$inputFilter->isValid()) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
            return $jsonModel;
        }

        $validatedData = $inputFilter->getValues();
        $validatedData['client'] = $postData['client'] ?? 'web';
        $errorMessageContainer = new Container('error_code');
        try {
            // Authenticate here
            /** @var ApiAuthenticateService */
            $authResponse = $this->apiAuthService->setPost($validatedData)->authenticate();
            $client = $validatedData['client'] ?? 'web';
            if ($client === 'web') {
                $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            }
            $response->getHeaders()->addHeaderLine('Cache-Control', 'no-store');
            $response->getHeaders()->addHeaderLine('Pragma', 'no-cache');
            $response->setStatusCode(200);
            
            $responseData = [
                'success' => true,
                'schema' => 'Bearer',
                'expires_in' => $authResponse['expire'],
                'token' => $authResponse['token'],
                'luhn_token' => $authResponse['token_id'],
                'user' => [
                    'fullname' => $authResponse['fullname'],
                    'email' => $authResponse['email'],
                    'role' => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid' => $authResponse['uuid'],
                    'wallet' => intval($authResponse['wallet']),
                    'profile_pic' => $authResponse['profile_pic'] ?? null
                ]
            ];
            if ($client === 'mobile' && isset($authResponse['refresh_token'])) {
                $responseData['refresh_token'] = $authResponse['refresh_token'];
            }
            $jsonModel->setVariables($responseData);
        } catch (\Throwable $th) {
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'AuthenticationError',
                'description' => $th->getMessage()
            ]);

            $response->setStatusCode($errorMessageContainer->code ?: 400);
        }

        return $jsonModel;
    }

    // =========================================================================
    // Token Refresh
    // =========================================================================

    /**
     * Exchange a valid refresh token for a new access token + rotated refresh token.
     *
     * @OA\Post(
     *     path="/auth/ipa/refresh",
     *     tags={"Authentication"},
     *     description="Exchange a valid refresh token for a new access token and a rotated refresh token. The old refresh token is immediately invalidated (token rotation). Send the refresh token in the Authorization header as `Bearer <refresh_token>`, or pass it in the JSON body.",
     *     @OA\RequestBody(
     *         required=false,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="refresh_token", type="string", description="Refresh token issued at login. Can also be sent via Authorization: Bearer header."),
     *                     @OA\Property(property="client", type="string", enum={"web", "mobile"}, default="web", description="Client type: 'web' returns HttpOnly cookie; 'mobile' returns refresh_token in response JSON body.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="New access token issued",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success",       type="boolean", example=true),
     *                     @OA\Property(property="schema",        type="string",  example="Bearer"),
     *                     @OA\Property(property="expires_in",    type="integer", example=1800),
     *                     @OA\Property(property="token",         type="string",  description="New access token"),
     *                     @OA\Property(property="refresh_token", type="string",  description="New rotated refresh token — store this and discard the old one"),
     *                     @OA\Property(property="luhn_token",    type="string",  example="rt_xyz789")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized (Invalid, revoked, or expired refresh token)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="Unauthorized"),
     *                     @OA\Property(property="description", type="string", example="Refresh token has been revoked or does not exist")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function refreshAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        try {
            // Accept refresh token from: 1) Authorization header, 2) JSON body, 3) HttpOnly cookie
            $refreshToken = null;

            $authHeader = $request->getHeader('Authorization');
            if ($authHeader) {
                $headerValue = $authHeader->getFieldValue();
                if (str_starts_with($headerValue, 'Bearer ')) {
                    $refreshToken = trim(substr($headerValue, 7));
                }
            }            $body = json_decode($request->getContent(), true) ?: [];
            $client = $body['client'] ?? 'web';

            if (empty($refreshToken)) {
                $refreshToken = $body['refresh_token'] ?? null;
            }

            if (empty($refreshToken)) {
                // Fall back to HttpOnly cookie
                $cookies = $request->getCookie();
                if ($cookies && $cookies->offsetExists(ApiAuthenticateService::COOKIE_NAME)) {
                    $refreshToken = $cookies->offsetGet(ApiAuthenticateService::COOKIE_NAME);
                }
            }

            if (empty($refreshToken)) {
                throw new \Exception('Refresh token is missing');
            }

            $authResponse = $this->apiAuthService->exchangeRefreshToken($refreshToken, $client);

            // Set the new rotated refresh token as HttpOnly cookie if client is web
            if ($client === 'web') {
                $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            }
            $response->getHeaders()->addHeaderLine('Cache-Control', 'no-store');
            $response->getHeaders()->addHeaderLine('Pragma', 'no-cache');
            $response->setStatusCode(200);
            
            $responseData = [
                'success' => true,
                'schema' => 'Bearer',
                'expires_in' => $authResponse['expire'],
                'token' => $authResponse['token'],
                'luhn_token' => $authResponse['token_id'],
                'user' => [
                    'fullname' => $authResponse['fullname'],
                    'email' => $authResponse['email'],
                    'role' => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid' => $authResponse['uuid'],
                    'wallet' => intval($authResponse['wallet']),
                    'profile_pic' => $authResponse['profile_pic'] ?? null
                ]
            ];
            if ($client === 'mobile' && isset($authResponse['refresh_token'])) {
                $responseData['refresh_token'] = $authResponse['refresh_token'];
            }
            $jsonModel->setVariables($responseData);
        } catch (\Throwable $th) {
            $response->setStatusCode(401);
            $jsonModel->setVariables([
                'success' => false,
                'description' => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    // =========================================================================
    // Logout — Revoke Refresh Token
    // =========================================================================

    /**
     * Logout and invalidate the current refresh token.
     *
     * @OA\POST(
     *     path="/auth/ipa/logout",
     *     tags={"Authentication"},
     *     description="Revokes the supplied refresh token so it can never be used again, logging out the user device session. Send the refresh token in the Authorization header as `Bearer <refresh_token>`, or pass it in the JSON body.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="refresh_token", type="string", description="Refresh token to revoke.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Logged out successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Logged out successfully")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. missing refresh token)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="LogoutError"),
     *                     @OA\Property(property="description", type="string", example="Refresh token is missing")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function logoutAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        try {
            $refreshToken = null;

            $authHeader = $request->getHeader('Authorization');
            if ($authHeader) {
                $headerValue = $authHeader->getFieldValue();
                if (str_starts_with($headerValue, 'Bearer ')) {
                    $refreshToken = trim(substr($headerValue, 7));
                }
            }

            if (empty($refreshToken)) {
                $body = json_decode($request->getContent(), true);
                $refreshToken = $body['refresh_token'] ?? null;
            }

            if (empty($refreshToken)) {
                $cookies = $request->getCookie();
                if ($cookies && $cookies->offsetExists(ApiAuthenticateService::COOKIE_NAME)) {
                    $refreshToken = $cookies->offsetGet(ApiAuthenticateService::COOKIE_NAME);
                }
            }

            if (empty($refreshToken)) {
                throw new \Exception('Refresh token is missing');
            }

            $this->apiAuthService->revokeRefreshToken($refreshToken);

            // Clear the cookie
            $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=; Max-Age=0; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            $response->setStatusCode(200);
            $jsonModel->setVariables(['success' => true, 'description' => 'Logged out successfully']);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables(['success' => false, 'description' => $th->getMessage()]);
        }

        return $jsonModel;
    }

    /**
     * Registers a Customer
     *
     * @OA\POST(
     *     path="/auth/ipa/register",
     *     tags={"Authentication"},
     *     description="Registers a new customer account in the system and triggers an email confirmation flow.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"username", "fullname", "email", "password", "confirm_password"},
     *                     @OA\Property(property="username", type="string", example="09012121212", description="Desired unique username (phone number recommended)"),
     *                     @OA\Property(property="fullname", type="string", example="Idowu Yusuf Chukwuma", description="Full legal name of the user"),
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="Valid, unique email address for verification"),
     *                     @OA\Property(property="password", type="string", example="Oluwaseun1", description="Plain text password meeting strength requirements"),
     *                     @OA\Property(property="confirm_password", type="string", example="Oluwaseun1", description="Must match password exactly")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="User registered successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="fullname", type="string", example="Idowu Yusuf Chukwuma"),
     *                         @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully Created Idowu Yusuf Chukwuma, profile, please visit email to confirm email")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. email or username already exists, passwords mismatch, or validation failed)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="RegisterError"),
     *                     @OA\Property(property="description", type="string", example="A user with this email already exists")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function registerAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        // Converts it into a PHP object
        $postData = (array) json_decode($json, true);

        try {
            $responseData = $this->registerService->register($postData);
            if (!is_null($responseData)) {
                $response->setStatusCode(201);
                $jsonModel->setVariables([
                    'success' => true,
                    'data' => [
                        'fullname' => $responseData['fullname'],
                        'email' => $responseData['email'],
                    ],
                    'description' => "Successfully Created {$responseData['fullname']}, profile, please visit Email to confirm email"
                ]);
            }
        } catch (\Throwable $th) {
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'RegistrationError',
                'description' => $th->getMessage()
            ]);
            $response->setStatusCode(400);
        }

        return $jsonModel;
    }

    /**
     * This API is used to verify the user's email address using the registration code.
     *
     * @OA\POST(
     *     path="/auth/ipa/verify",
     *     tags={"Authentication"},
     *     description="Verifies the user's email address using a verification code sent during registration. On successful verification, the account state is set to Enabled.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"code", "email"},
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="The user's registered email address"),
     *                     @OA\Property(property="code", type="string", example="345634", description="The verification code sent to the user's email")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Email verified successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Email verified successfully")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. invalid code, email not found, or registration code mismatch)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="VerificationError"),
     *                     @OA\Property(property="description", type="string", example="Invalid verification code")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function verifyAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $son = $request->getContent();
        $postData = json_decode($son, true);
        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'code',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Code is required'
                        ]
                    ]
                ]
            ]
        ]);

        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Email is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->setData($postData);
        if ($inputFilter->isValid()) {
            try {
                $data = $inputFilter->getValues();
                $this->registerService->confirmEmailMobile($data);

                $jsonModel->setVariables([
                    'success' => true
                ]);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'VerificationError',
                    'description' => $th->getMessage()
                ]);
            }
        } else {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
        }
        return $jsonModel;
    }

    /**
     * Request another confirmation code.
     *
     * @OA\POST(
     *     path="/auth/ipa/resend-mobile-code",
     *     tags={"Authentication"},
     *     description="Generates a new verification code and resends it to the user's registered email or phone.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"email"},
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="Registered email address of the user")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Verification code resent successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Confirmation code resent successfully")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. email not found, validation error)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ValidationError"),
     *                     @OA\Property(property="description", type="string", example="Email is required")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function resendMobileCodeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        $postData = json_decode($json, true);
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Email is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->setData($postData);
        if ($inputFilter->isValid()) {
            try {
                $data = $inputFilter->getValues();
                $mailData['email'] = $data['email'];
                $mailData['code'] = RegisterService::generateMobileCode();
                /** @var User */
                $userEntity = $em->getRepository(User::class)->findOneBy([
                    'email' => $data['email']
                ]);
                if (!$userEntity) {
                    throw new \Exception('User does not exist');
                }
                $userEntity->setUpdatedOn(new \Datetime())->setMobileActivateCode($mailData['code']);
                $em->persist($userEntity);
                $em->flush();
                $this->mailtrapService->confirmEmail($mailData);
                $jsonModel->setVariables([
                    'success' => true
                ]);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'ProcessError',
                    'description' => $th->getMessage()
                ]);
            }
        } else {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
        }
        return $jsonModel;
    }

    /**
     * Reteieves a refresh token based on the validity of the old one
     * @OA\GET( path="/auth/ipa/refresh-token", tags={"Authentication"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response="201", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *                     @OA\Property(
     *                         property="schema",
     *                         type="string",
     *                         description="The type  of Authentication "
     *                     ),
     *
     *                    @OA\Property(
     *                         property="expires_in",
     *                         type="integer",
     *                         description="The response message"
     *                     ),
     *                    @OA\Property(
     *                         property="token",
     *                         type="string",
     *                         description="The Bearer token"
     *                     ),
     *                     @OA\Property(
     *                         property="luhn_token",
     *                         type="array",
     *                         description="Unique identifierfor the bearer token",
     *                         @OA\Items
     *                     ),
     *                     example={
     *                         "success": true,
     *                         "schema": "Bearer",
     *                         "expires_in": 7200,
     *                        "token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdCIsImp0aSI6ImV6ZWtpZWxfYUB5YWhvby5jb20iLCJzdWIiOiJlemVraWVsX2FAeWFob28uY29tIiwiY29kZWQiOnsidXVpZCI6IjBhZjM1ODU3LWZhNTQtNGNiZi1iNTllLWUyYWI1ZjUzN2Y3MyIsInVpZCI6InJlc3U2NDUwMTk3MjZhNDc5IiwiYXVkIjoiNGJlODBlMTgtMmNlNi00YzM1LTgzYzEtNDU1NGVlMGM3ZjNlIiwiZW1haWwiOiJlemVraWVsX2FAeWFob28uY29tIiwicm9sZSI6MTAwLCJ0b2tlbl9pZCI6IjA2MzZiMTNkLThkNzQtNDRjYy05YjlhLWRhOThjZmU2MGI3MiJ9LCJpYXQiOjE2ODY4OTYxNzUuODAwNzY0LCJleHAiOjE2ODY4OTgwOTUuODAwNzY0fQ.mI71-srLbpX6V0g9yyFsaN4pKoa8UXydbG_td2_wu8g",
     *                         "luhn_token": "26a14737-407b-4579-9f9d-a1df668e060a"
     *                     }
     *                 )
     *             )
     *         } ),
     * @OA\Response(response="400", description="Error", content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         description="Information about the error "
     *                     ),
     *       @OA\Property(
     *                         property="error",
     *                         type="string",
     *                         description="Provide more information about the error "
     *                     ),
     *
     *
     *                     example={
     *                         "success": false,
     *                        "error":"Validation",
     *                         "message": "Something went wrong",
     *
     *                     }
     *                 )
     *             )
     *         }),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function refreshTokenAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $api_auth = $this->apiAuthService;
            $refreshData = $api_auth->refreshTokenIdentity();
            $token_id = $refreshData['token_id'];
            $user_uuid = $refreshData['uuid'];
            /** @var UserRefreshToken */
            $refreshTokenEntity = $this->entityManager->getRepository(UserRefreshToken::class)->findOneBy([
                'tokenId' => $token_id
            ]);
            $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
                'uuid' => $user_uuid
            ]);
            $authResponse = '';
            if ($refreshTokenEntity != null) {
                $authResponse = $api_auth->generateRefreshToken($refreshTokenEntity, $userEntity);
                $response->setStatusCode(201);
                $jsonModel->setVariables([
                    'success' => true,
                    'schema' => 'Bearer',
                    'expires_in' => $authResponse['expire'],
                    'token' => $authResponse['token'],
                    'luhn_token' => $authResponse['token_id'],  // luhn algorithm value
                ]);
            }
        } catch (\Throwable $th) {
            $jsonModel->setVariables([
                'success' => false,
                'description' => $th->getMessage(),
                // "data" => $th->getTrace()
            ]);

            $response->setStatusCode(400);

            //
        }
        return $jsonModel;
    }

    /**
     * Initiate password reset flow.
     *
     * @OA\POST(
     *     path="/auth/ipa/initiate-change-password",
     *     tags={"Authentication"},
     *     description="Initiates a password reset flow for the user with the given email address. A numeric reset code is generated, stored, and sent to the user's email.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"email"},
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="User's registered email address")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Reset code generated and email sent successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Reset code generated and email sent")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. email not found, validation error)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ValidationError"),
     *                     @OA\Property(property="description", type="string", example="Email is required")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function initiateChangePasswordAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        $postData = json_decode($json, true);
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Email is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->setData($postData);
        $em = $this->entityManager;

        if ($inputFilter->isValid()) {
            $values = $inputFilter->getValues();
            try {
                /** @var User */
                $userEntity = $em->getRepository(User::class)->findOneBy([
                    'email' => $values['email']
                ]);
                if ($userEntity == null) {
                    throw new \Exception('User does not exist');
                }

                $resetCode = RegisterService::generateMobileCode();
                $userEntity->setMobileActivateCode($resetCode)->setUpdatedOn(new \Datetime());
                $em->persist($userEntity);
                $em->flush();

                $mailData = [
                    'to' => $userEntity->getEmail(),
                    'code' => $resetCode,
                    'subject' => 'Recyclepoint Reset Password',
                    'toName' => $userEntity->getFullname(),
                    'fulllink' => $resetCode
                ];

                $this->authPostmarkService->resetpassword($mailData);

                $jsonModel->setVariables([
                    'success' => true
                ]);
                $response->setStatusCode(200);
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'ResetError',
                    'description' => $th->getMessage()
                ]);
                $response->setStatusCode(400);
            }
        } else {
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
            $response->setStatusCode(400);
        }
        return $jsonModel;
    }

    /**
     * Confirm password reset code.
     *
     * @OA\POST(
     *     path="/auth/ipa/confirm-reset-code",
     *     tags={"Authentication"},
     *     description="Validates the password reset code sent to the user's email. On successful validation, returns the confirmed code to be used in the password update step.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"code", "email"},
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="User's registered email address"),
     *                     @OA\Property(property="code", type="string", example="340958", description="Reset code received via email")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Code confirmed successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="reset_code", type="string", example="340958", description="Confirmed reset code"),
     *                     @OA\Property(property="description", type="string", example="Code Confirmed")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. invalid code, mismatched email, or expired code)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ResetCodeError"),
     *                     @OA\Property(property="description", type="string", example="Invalid or mismatched reset code")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function confirmResetCodeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        $postData = json_decode($json, true);
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'code',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Code is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Email is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->setData($postData);
        if ($inputFilter->isValid()) {
            try {
                $values = $inputFilter->getValues();
                $newCode = RegisterService::generateMobileCode();
                /** @var User */
                $userEntity = $em->getRepository(User::class)->findOneBy([
                    'email' => $values['email']
                ]);
                if ($userEntity == null) {
                    throw new \Exception('User does not exist');
                }
                if ($values['code'] == $userEntity->getMobileActivateCode()) {
                    $userEntity->setMobileActivateCode($newCode)->setUpdatedOn(new \Datetime());

                    $em->persist($userEntity);
                    $em->flush();
                    $response->setStatusCode(201);
                    $jsonModel->setVariables([
                        'success' => true,
                        'reset_code' => $userEntity->getMobileActivateCode(),
                        'description' => 'Code Confirmed'
                    ]);
                    return $jsonModel;
                } else {
                    throw new \Exception('Invalid Code');
                }
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'ConfirmCodeError',
                    'description' => $th->getMessage()
                ]);
                $response->setStatusCode(400);
            }
        } else {
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
            $response->setStatusCode(400);
        }
        return $jsonModel;
    }

    /**
     * Updates user password.
     *
     * @OA\POST(
     *     path="/auth/ipa/update-password",
     *     tags={"Authentication"},
     *     description="Updates user password to the new value. Requires the confirmed reset_code generated in the verify step, email address, password, and confirmation password.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"password", "email", "confirm_password", "reset_code"},
     *                     @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com", description="User's registered email address"),
     *                     @OA\Property(property="password", type="string", example="gthr!@#ju", description="New plain text password"),
     *                     @OA\Property(property="reset_code", type="string", example="123456", description="The confirmed reset code"),
     *                     @OA\Property(property="confirm_password", type="string", example="gthr!@#ju", description="Must match the new password exactly")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Password updated successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Password updated successfully")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. invalid code, passwords do not match, validation error)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="UpdatePasswordError"),
     *                     @OA\Property(property="description", type="string", example="Passwords do not match")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed. Use POST.")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function updatePasswordAction()
    {
        $request = $this->getRequest();
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        $postData = json_decode($json, true);
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name' => 'password',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 6,
                        'messages' => [
                            StringLength::TOO_SHORT => 'The password must be more than 6 characters',
                            StringLength::TOO_LONG => 'This password is too long to memorize'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 6,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Email is too short',
                            StringLength::TOO_LONG => 'This Email is too long to memorize'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->add([
            'name' => 'reset_code',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Reset code is required'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->add([
            'name' => 'confirm_password',
            'required' => true,
            'allow_empty' => false,
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 6,
                        'messages' => [
                            StringLength::TOO_SHORT => 'The password must be more than 6 characters',
                            StringLength::TOO_LONG => 'This password is too long to memorize'
                        ]
                    ]
                ],
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password',
                        'messages' => [
                            Identical::NOT_SAME => 'The passwords are not identical'
                        ]
                    ]
                ]
            ]
        ]);
        $inputFilter->setData($postData);
        $em = $this->entityManager;
        if ($inputFilter->isValid()) {
            $values = $inputFilter->getValues();

            try {
                /** @var User */
                $userEntity = $em->getRepository(User::class)->findOneBy([
                    'email' => $values['email']
                ]);

                if ($userEntity == null) {
                    throw new \Exception('User does not exist');
                }

                if ($values['reset_code'] != $userEntity->getMobileActivateCode()) {
                    throw new \Exception('Wrong access code');
                }

                $userEntity->setPassword(AuthenticationService::encryptPassword($values['password']))->setUpdatedOn(new \Datetime());

                $em->persist($userEntity);
                $em->flush();

                $jsonModel->setVariables([
                    'success' => true
                ]);
                $response->setStatusCode(201);
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'PasswordUpdateError',
                    'description' => $th->getMessage()
                ]);
                $response->setStatusCode(400);
            }
        } else {
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'ValidationError',
                'description' => $inputFilter->getMessages()
            ]);
            $response->setStatusCode(400);
        }
        return $jsonModel;
    }

    // /**
    //  * Verifies Email of the User
    //  *
    //  * @return void
    //  */
    // public function verifyAction()
    // {
    //     $jsonModel = new JsonModel();
    //     return $jsonModel;
    // }

    // /**
    //  * @OA\Post(
    //  *   path="/auth/jwt/login",
    //  *   tags={"Auth"},
    //  *   summary="JWT login",
    //  *   description="Login a user and generate JWT token",
    //  *   operationId="jwtLogin",
    //  *   @OA\RequestBody(
    //  *       required=true,
    //  *       @OA\MediaType(
    //  *           mediaType="application/json",
    //  *           @OA\Schema(
    //  *               type="object",
    //  *               @OA\Property(
    //  *                   property="email",
    //  *                   description="User email",
    //  *                   type="string",
    //  *                   example="ihamzehald@gmail.com"
    //  *               ),
    //  *               @OA\Property(
    //  *                   property="password",
    //  *                   description="User password",
    //  *                   type="string",
    //  *                   example="larapoints123"
    //  *               ),
    //  *           )
    //  *       )
    //  *   ),
    //  *  @OA\Response(
    //  *         response="200",
    //  *         description="ok",
    //  *         content={
    //  *             @OA\MediaType(
    //  *                 mediaType="application/json",
    //  *                 @OA\Schema(
    //  *                     @OA\Property(
    //  *                         property="access_token",
    //  *                         type="string",
    //  *                         description="JWT access token"
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="token_type",
    //  *                         type="string",
    //  *                         description="Token type"
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="expires_in",
    //  *                         type="integer",
    //  *                         description="Token expiration in miliseconds",
    //  *                         @OA\Items
    //  *                     ),
    //  *                     example={
    //  *                         "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    //  *                         "token_type": "bearer",
    //  *                         "expires_in": 3600
    //  *                     }
    //  *                 )
    //  *             )
    //  *         }
    //  *     ),
    //  *   @OA\Response(response="401",description="Unauthorized"),
    //  * )
    //  */

    /**
     * Get doctrine ORM EntityManager
     *
     * @return  EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set doctrine ORM EntityManager
     *
     * @param  EntityManager  $entityManager  Doctrine ORM EntityManager
     *
     * @return  self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Get generalSerive Class
     *
     * @return  GeneralService
     */
    public function getGeneralService()
    {
        return $this->generalService;
    }

    /**
     * Set generalSerive Class
     *
     * @param  GeneralService  $generalService  GeneralSerive Class
     *
     * @return  self
     */
    public function setGeneralService(GeneralService $generalService)
    {
        $this->generalService = $generalService;

        return $this;
    }

    /**
     * Get register Service
     *
     * @return  RegisterService
     */
    public function getRegisterService()
    {
        return $this->registerService;
    }

    /**
     * Set register Service
     *
     * @param  RegisterService  $registerService  Register Service
     *
     * @return  self
     */
    public function setRegisterService(RegisterService $registerService)
    {
        $this->registerService = $registerService;

        return $this;
    }

    /**
     * Get api Athentication Service
     *
     * @return  ApiAuthenticateService
     */
    public function getApiAuthService()
    {
        return $this->apiAuthService;
    }

    /**
     * Set api Athentication Service
     *
     * @param  ApiAuthenticateService  $apiAuthService  Api Athentication Service
     *
     * @return  self
     */
    public function setApiAuthService(ApiAuthenticateService $apiAuthService)
    {
        $this->apiAuthService = $apiAuthService;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  MailtrapService
     */
    public function getMailtrapService()
    {
        return $this->mailtrapService;
    }

    /**
     * Set undocumented variable
     *
     * @param  MailtrapService  $mailtrapService  Undocumented variable
     *
     * @return  self
     */
    public function setMailtrapService(MailtrapService $mailtrapService)
    {
        $this->mailtrapService = $mailtrapService;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  JwtIssuer
     */
    public function getJwtIssuer()
    {
        return $this->jwtIssuer;
    }

    /**
     * Set undocumented variable
     *
     * @param  JwtIssuer  $jwtIssuer  Undocumented variable
     *
     * @return  self
     */
    public function setJwtIssuer(JwtIssuer $jwtIssuer)
    {
        $this->jwtIssuer = $jwtIssuer;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  AuthMailtrapService
     */
    public function getAuthMailtrapService()
    {
        return $this->authMailtrapService;
    }

    /**
     * Set undocumented variable
     *
     * @param  AuthMailtrapService  $authMailtrapService  Undocumented variable
     *
     * @return  self
     */
    public function setAuthMailtrapService(AuthMailtrapService $authMailtrapService)
    {
        $this->authMailtrapService = $authMailtrapService;

        return $this;
    }

    public function setAuthPostmarkService(AuthenticationEmailService $authPostmarkService)
    {
        $this->authPostmarkService = $authPostmarkService;

        return $this;
    }

    /**
     * Social Authentication
     * @OA\POST(
     *     path="/auth/ipa/social-login",
     *     tags={"Authentication"},
     *     description="Authenticate user with social provider (Google or Apple). Validates the ID token directly with the provider, logs in or auto-registers the user. If client is 'web', sets an HttpOnly cookie with the rotated refresh token; if client is 'mobile', returns the refresh token directly in the response body.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"provider", "token", "user_agent", "user_ip"},
     *                     @OA\Property(property="provider", type="string", example="google", description="The social identity provider (google or apple)"),
     *                     @OA\Property(property="token", type="string", example="eyJhbGci...", description="The ID Token from Google or Apple SDK"),
     *                     @OA\Property(property="user_agent", type="string", example="AppleWebKit/535.19"),
     *                     @OA\Property(property="user_ip", type="string", example="127.0.0.1"),
     *                     @OA\Property(property="client", type="string", enum={"web", "mobile"}, default="web", description="Client type: 'web' returns HttpOnly cookie; 'mobile' returns refresh_token in response JSON body.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="schema", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_in", type="integer", example=1800),
     *                     @OA\Property(property="token", type="string", example="eyJhbGci..."),
     *                     @OA\Property(property="luhn_token", type="string", example="rt_abc123"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="fullname", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="Guardian"),
     *                         @OA\Property(property="username", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="uuid", type="string", example="d3b07384-d113-4956-a5db-e172e2cf69ef"),
     *                         @OA\Property(property="wallet", type="integer", example=150)
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. invalid provider token, missing parameters)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="SocialLoginError"),
     *                     @OA\Property(property="description", type="string", example="Failed to verify Google Token")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed (e.g. GET instead of POST)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="MethodNotAllowed"),
     *                     @OA\Property(property="description", type="string", example="Method Not Allowed")
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function socialLoginAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);

            $provider = strtolower($postData['provider'] ?? '');
            $idToken = $postData['token'] ?? '';
            $userAgent = $postData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
            $userIp = $postData['user_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            try {
                if (empty($provider) || empty($idToken)) {
                    throw new \Exception('Provider and token are required');
                }

                $email = '';
                $name = '';
                $providerId = '';
                $profilePic = null;

                $sm = $this->getEvent()->getApplication()->getServiceManager();
                $config = $sm->get('config');

                if ($provider === 'google') {
                    $googleConfig = $config['google_oauth'] ?? [];
                    $clientId = $googleConfig['client_id'] ?? '';
                    if (empty($clientId)) {
                        throw new \Exception('Google OAuth configuration is missing on the server');
                    }

                    $payload = $this->verifyGoogleIdToken($idToken, $clientId);
                    $email = $payload['email'];
                    $name = $payload['name'] ?? '';
                    $providerId = $payload['sub'] ?? '';
                    $profilePic = $payload['picture'] ?? null;
                } elseif ($provider === 'apple') {
                    $appleConfig = $config['apple_oauth'] ?? [];
                    $clientId = $appleConfig['client_id'] ?? '';
                    if (empty($clientId)) {
                        throw new \Exception('Apple OAuth configuration is missing on the server');
                    }

                    $payload = $this->verifyAppleIdToken($idToken, $clientId);
                    $email = $payload['email'];
                    $providerId = $payload['sub'];
                    $name = '';  // Apple name is only sent once via client SDK OAuth flow
                } else {
                    throw new \Exception('Unsupported provider: ' . $provider);
                }

                $client = $postData['client'] ?? 'web';
                $authResponse = $this->apiAuthService->authenticateSocial($email, $name, $provider, $providerId, $userIp, $userAgent, $profilePic, $client);
                if ($client === 'web') {
                    $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
                }
                $response->setStatusCode(200);

                $responseData = [
                    'success' => true,
                    'schema' => 'Bearer',
                    'expires_in' => $authResponse['expire'],
                    'token' => $authResponse['token'],
                    'luhn_token' => $authResponse['token_id'],
                    'user' => [
                        'fullname' => $authResponse['fullname'],
                        'email' => $authResponse['email'],
                        'role' => $authResponse['role'],
                        'username' => $authResponse['username'],
                        'uuid' => $authResponse['uuid'],
                        'wallet' => intval($authResponse['wallet'])
                    ]
                ];
                if ($client === 'mobile' && isset($authResponse['refresh_token'])) {
                    $responseData['refresh_token'] = $authResponse['refresh_token'];
                }
                $jsonModel->setVariables($responseData);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    'success' => false,
                    'error' => 'SocialLoginError',
                    'description' => $th->getMessage()
                ]);
            }
        } else {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'description' => 'Method Not Allowed'
            ]);
        }

        return $jsonModel;
    }

    /**
     * Google OAuth (Initiate or Callback)
     * @OA\GET(
     *     path="/auth/ipa/google-initiate",
     *     tags={"Authentication"},
     *     description="Handles Google OAuth. If the 'code' parameter is absent, redirects the user to Google. If present, processes the callback to authenticate the user.",
     *     @OA\Parameter(name="code", in="query", required=false, description="Authorization code returned by Google", @OA\Schema(type="string")),
     *     @OA\Parameter(name="state", in="query", required=false, description="Stateless HMAC-signed anti-forgery token", @OA\Schema(type="string")),
     *     @OA\Response(response="302", description="Redirect to Google authorization page (when code is missing)"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="schema", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_in", type="integer", example=1800),
     *                     @OA\Property(property="token", type="string", example="eyJhbGci..."),
     *                     @OA\Property(property="luhn_token", type="string", example="rt_abc123"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="fullname", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="Guardian"),
     *                         @OA\Property(property="username", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="uuid", type="string", example="d3b07384-d113-4956-a5db-e172e2cf69ef"),
     *                         @OA\Property(property="wallet", type="integer", example=150),
     *                         @OA\Property(property="profile_pic", type="string", example="https://...")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     */

    /**
     * Google OAuth Initiate
     * @OA\GET(
     *     path="/auth/ipa/google-initiate",
     *     tags={"Authentication"},
     *     description="Generates a stateless HMAC-signed anti-forgery state token and redirects to Google's authorization URL.",
     *     @OA\Response(response="302", description="Redirect to Google authorization page"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     */
    public function googleInitiateAction()
    {
        // Fetch the response object that will contain the response headers
        $response = $this->getResponse();
        // Initialize a new JsonModel for structured JSON responses
        $jsonModel = new JsonModel();

        try {
            // Retrieve the application Service Manager instance
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            // Retrieve the application configuration array
            $config = $sm->get('config');
            // Fetch the Google OAuth specific configurations or default to empty array
            $googleConfig = $config['google_oauth'] ?? [];

            // Get the configured Google Client ID
            $clientId = $googleConfig['client_id'] ?? '';
            // Get the configured redirect URI for the callback
            $redirectUri = $googleConfig['redirect_uri'] ?? '';
            // Get the requested scope or default to openid, email, and profile
            $scope = $googleConfig['scope'] ?? 'openid email profile';

            // Check if the critical client ID and redirect URI configurations exist
            if (empty($clientId) || empty($redirectUri)) {
                // Throw an exception if the required configuration is missing
                throw new \Exception('Google OAuth configuration is missing on the server');
            }

            // Generate a stateless HMAC-signed state token for anti-forgery verification
            $state = $this->generateOAuthState($sm);

            // Build the Google OAuth authorization redirect URL with query params
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'state' => $state,
                'access_type' => 'online',
            ]);

            // Add the Location header to redirect to Google's sign-in page
            $response->getHeaders()->addHeaderLine('Location', $authUrl);
            // Set the HTTP response status code to 302 Found
            $response->setStatusCode(302);
            // Return the response object to perform the redirect
            return $response;
        } catch (\Throwable $th) {
            // Set HTTP response code to 500
            $response->setStatusCode(500);
            // Populate JsonModel with the error details
            $jsonModel->setVariables([
                'success' => false,
                'description' => $th->getMessage()
            ]);
            // Return the JsonModel
            return $jsonModel;
        }
    }

    /**
     * Google Sign-In via Authorization Code
     * @OA\POST(
     *     path="/auth/ipa/google-oauth",
     *     tags={"Authentication"},
     *     description="Authenticates a user via Google OAuth2. Exchanges the authorization code, verifies the ID Token signature/claims/nonce, finds or creates the local user. If client is 'web', sets an HttpOnly cookie with the rotated refresh token; if client is 'mobile', returns the refresh token directly in the response body.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"code", "redirect_uri", "code_verifier", "nonce"},
     *                     @OA\Property(property="code", type="string", description="Authorization code from Google"),
     *                     @OA\Property(property="redirect_uri", type="string", description="Redirect URI passed in Google authorize request"),
     *                     @OA\Property(property="code_verifier", type="string", description="PKCE code verifier matching the code challenge"),
     *                     @OA\Property(property="nonce", type="string", description="Replay prevention nonce matching the initial authorize request"),
     *                     @OA\Property(property="client", type="string", enum={"web", "mobile"}, default="web", description="Client type: 'web' returns HttpOnly cookie; 'mobile' returns refresh_token in response JSON body.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="schema", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_in", type="integer", example=1800),
     *                     @OA\Property(property="token", type="string", example="eyJhbGci..."),
     *                     @OA\Property(property="access_token", type="string", example="eyJhbGci..."),
     *                     @OA\Property(property="luhn_token", type="string", example="rt_abc123"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="1"),
     *                         @OA\Property(property="fullname", type="string", example="John Doe"),
     *                         @OA\Property(property="display_name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="Guardian"),
     *                         @OA\Property(property="username", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="uuid", type="string", example="d3b07384-d113-4956-a5db-e172e2cf69ef"),
     *                         @OA\Property(property="wallet", type="integer", example=150),
     *                         @OA\Property(property="profile_pic", type="string", example="https://...")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     */
    public function googleOauthAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use POST.'
            ]);
            return $jsonModel;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            if (!is_array($postData)) {
                throw new \Exception('Invalid JSON body');
            }

            $code = trim((string) ($postData['code'] ?? ''));
            $redirectUri = trim((string) ($postData['redirect_uri'] ?? ''));
            $codeVerifier = trim((string) ($postData['code_verifier'] ?? ''));
            $nonce = trim((string) ($postData['nonce'] ?? ''));

            if (empty($code) || empty($redirectUri) || empty($codeVerifier) || empty($nonce)) {
                throw new \Exception('code, redirect_uri, code_verifier, and nonce are required');
            }

            // 1. Exchange Google authorization code for tokens
            $googleTokens = $this->googleAuthService->exchangeAuthorizationCode($code, $redirectUri, $codeVerifier);
            $idToken = $googleTokens['id_token'] ?? null;
            if (empty($idToken)) {
                throw new \Exception('Google did not return an ID token');
            }

            // 2. Verify Google ID Token
            $tokenPayload = $this->googleAuthService->validateIdToken($idToken, $nonce);

            $email = $tokenPayload['email'];
            $name = $tokenPayload['name'] ?? '';
            $providerId = $tokenPayload['sub'] ?? '';
            $profilePic = $tokenPayload['picture'] ?? null;

            // 3. Issue access JWT and refresh token using social authentication service
            $client = $postData['client'] ?? 'web';
            $authResponse = $this->apiAuthService->authenticateSocial($email, $name, 'google', $providerId, $userIp, $userAgent, $profilePic, $client);

            // Set the HttpOnly refresh token cookie on the response headers if client is web
            if ($client === 'web') {
                $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            }

            // Set the response status code to 200 OK
            $response->setStatusCode(200);

            // Populate the JsonModel with final user profile and tokens (standard and compatibility fields)
            $responseData = [
                'success' => true,
                'schema' => 'Bearer',
                'expires_in' => $authResponse['expire'],
                'token' => $authResponse['token'],
                'access_token' => $authResponse['token'],
                'luhn_token' => $authResponse['token_id'],
                'user' => [
                    'id' => (string) $authResponse['userid'],
                    'fullname' => $authResponse['fullname'],
                    'display_name' => $authResponse['fullname'],
                    'email' => $authResponse['email'],
                    'role' => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid' => $authResponse['uuid'],
                    'wallet' => intval($authResponse['wallet']),
                    'profile_pic' => $authResponse['profile_pic'] ?? null
                ]
            ];
            if ($client === 'mobile' && isset($authResponse['refresh_token'])) {
                $responseData['refresh_token'] = $authResponse['refresh_token'];
            }
            $jsonModel->setVariables($responseData);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'description' => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Google OAuth Callback Action (Handles redirect callback)
     * @OA\GET(
     *     path="/auth/ipa/google-callback",
     *     tags={"Authentication"},
     *     description="Handles Google OAuth callback. Exchanges redirect code for an ID Token, validates it, logs in/registers user, and redirects to frontend. Supports client='web' (sets HttpOnly cookie) and client='mobile' (passes refresh_token in redirect query params or JSON body).",
     *     @OA\Parameter(name="code", in="query", required=true, description="Authorization code from Google", @OA\Schema(type="string")),
     *     @OA\Parameter(name="state", in="query", required=true, description="CSRF state token", @OA\Schema(type="string")),
     *     @OA\Parameter(name="client", in="query", required=false, description="Client type ('web' or 'mobile')", @OA\Schema(type="string")),
     *     @OA\Response(response="302", description="Redirect to frontend redirect URL with tokens in query params"),
     *     @OA\Response(response="400", description="Bad Request")
     * )
     */
    public function googleCallbackAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        $code = $request->getQuery('code');
        $state = $request->getQuery('state');
        $error = $request->getQuery('error');

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            if ($error) {
                throw new \Exception('Google authentication error: ' . $request->getQuery('error_description', $error));
            }

            if (empty($code) || empty($state)) {
                throw new \Exception('Code and state are required');
            }

            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $config = $sm->get('config');
            $googleConfig = $config['google_oauth'] ?? [];

            $clientId = $googleConfig['client_id'] ?? '';
            $clientSecret = $googleConfig['client_secret'] ?? '';
            $redirectUri = $googleConfig['redirect_uri'] ?? '';

            if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
                throw new \Exception('Google OAuth configuration is incomplete on the server');
            }

            // CSRF protection: verify state
            $this->verifyOAuthState($state, $sm);

            // Exchange Authorization Code for ID Token
            $client = new \Laminas\Http\Client();
            $client->setOptions([
                'sslverifypeer' => false,
                'sslverifyhost' => false,
            ]);
            $client->setUri('https://oauth2.googleapis.com/token');
            $client->setMethod('POST');
            $client->setParameterPost([
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            $res = $client->send();
            if (!$res->isSuccess()) {
                throw new \Exception('Failed to exchange Google authorization code: ' . $res->getBody());
            }

            $tokenData = json_decode($res->getBody(), true);
            $idToken = $tokenData['id_token'] ?? '';
            if (empty($idToken)) {
                throw new \Exception('Google did not return an ID token');
            }

            // Verify Google ID Token
            $payload = $this->verifyGoogleIdToken($idToken, $clientId);

            $email = $payload['email'];
            $name = $payload['name'] ?? '';
            $providerId = $payload['sub'] ?? '';
            $profilePic = $payload['picture'] ?? null;

            // Authenticate and issue application tokens
            $clientParam = $request->getQuery('client') ?? 'web';
            if (empty($clientParam) && !empty($state)) {
                $stateData = json_decode($state, true);
                if (isset($stateData['client'])) {
                    $clientParam = $stateData['client'];
                }
            }
            $authResponse = $this->apiAuthService->authenticateSocial($email, $name, 'google', $providerId, $userIp, $userAgent, $profilePic, $clientParam);

            // Set the HttpOnly cookie if client is web
            if ($clientParam === 'web') {
                $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            }

            // Redirect to frontend
            $frontendRedirect = $googleConfig['frontend_redirect_url'] ?? '';
            if (!empty($frontendRedirect)) {
                $redirectParams = [
                    'token' => $authResponse['token'],
                    'fullname' => $authResponse['fullname']
                ];
                if (isset($authResponse['refresh_token'])) {
                    $redirectParams['refresh_token'] = $authResponse['refresh_token'];
                } else {
                    $redirectParams['refresh_token'] = $authResponse['token_id'];
                }
                $redirectUrl = $frontendRedirect . '?' . http_build_query($redirectParams);
                $response->getHeaders()->addHeaderLine('Location', $redirectUrl);
                $response->setStatusCode(302);
                return $response;
            }

            // Fallback to JSON
            $response->setStatusCode(200);
            $responseData = [
                'success' => true,
                'schema' => 'Bearer',
                'expires_in' => $authResponse['expire'],
                'token' => $authResponse['token'],
                'luhn_token' => $authResponse['token_id'],
                'user' => [
                    'fullname' => $authResponse['fullname'],
                    'email' => $authResponse['email'],
                    'role' => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid' => $authResponse['uuid'],
                    'wallet' => intval($authResponse['wallet']),
                    'profile_pic' => $authResponse['profile_pic'] ?? null
                ]
            ];
            if (isset($authResponse['refresh_token'])) {
                $responseData['refresh_token'] = $authResponse['refresh_token'];
            }
            $jsonModel->setVariables($responseData);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'GoogleCallbackError',
                'description' => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Apple Sign-In (Initiate or Callback)
     * @OA\POST(
     *     path="/auth/ipa/apple-initiate",
     *     tags={"Authentication"},
     *     description="Handles Apple OAuth. If parameters (code or id_token) are absent, redirects to Apple's authorization page. If present, processes the callback to authenticate the user. Supports client='web' (sets HttpOnly cookie) and client='mobile' (passes refresh_token in response JSON body).",
     *     @OA\RequestBody(
     *         required=false,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/x-www-form-urlencoded",
     *                 @OA\Schema(
     *                     @OA\Property(property="code", type="string", description="Authorization code from Apple"),
     *                     @OA\Property(property="id_token", type="string", description="Identity Token (JWT) from Apple"),
     *                     @OA\Property(property="state", type="string", description="State parameter passed in request"),
     *                     @OA\Property(property="user", type="string", description="JSON string with user name/email (first-time login)"),
     *                     @OA\Property(property="client", type="string", enum={"web", "mobile"}, default="web", description="Client type: 'web' returns HttpOnly cookie; 'mobile' returns refresh_token in response JSON body.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="302", description="Redirect to Apple authorization page (when id_token is missing)"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="schema", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_in", type="integer", example=1800),
     *                     @OA\Property(property="token", type="string", example="eyJhbGci..."),
     *                     @OA\Property(property="luhn_token", type="string", example="rt_abc123"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="fullname", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="Guardian"),
     *                         @OA\Property(property="username", type="string", example="john.doe@gmail.com"),
     *                         @OA\Property(property="uuid", type="string", example="d3b07384-d113-4956-a5db-e172e2cf69ef"),
     *                         @OA\Property(property="wallet", type="integer", example=150),
     *                         @OA\Property(property="profile_pic", type="string", example="null")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     * @OA\GET(
     *     path="/auth/ipa/apple-initiate",
     *     tags={"Authentication"},
     *     description="Apple OAuth redirect callback endpoint (GET backup/initiate)",
     *     @OA\Parameter(name="code", in="query", required=false, description="Authorization code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="id_token", in="query", required=false, description="Identity Token (JWT)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="client", in="query", required=false, description="Client type ('web' or 'mobile')", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="302", description="Redirect to Apple authorization page (when id_token is missing)"),
     *     @OA\Response(response="400", description="Error")
     * )
     */
    public function appleInitiateAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        // Apple sends parameters as POST form data (form_post) or query params
        $code = $request->getPost('code') ?? $request->getQuery('code');
        $idToken = $request->getPost('id_token') ?? $request->getQuery('id_token');
        $userParam = $request->getPost('user') ?? $request->getQuery('user');
        $state = $request->getPost('state') ?? $request->getQuery('state');

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $config = $sm->get('config');
            $appleConfig = $config['apple_oauth'] ?? [];

            $clientId = $appleConfig['client_id'] ?? '';
            $redirectUri = $appleConfig['redirect_uri'] ?? '';
            $scope = $appleConfig['scope'] ?? 'name email';
            $responseMode = $appleConfig['response_mode'] ?? 'form_post';

            if (empty($clientId) || empty($redirectUri)) {
                throw new \Exception('Apple OAuth configuration is incomplete');
            }

            // If id_token is absent, run Initiate logic
            if (empty($idToken)) {
                $state = $this->generateOAuthState($sm);

                $authUrl = 'https://appleid.apple.com/auth/authorize?' . http_build_query([
                    'response_type' => 'code id_token',
                    'response_mode' => $responseMode,
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'state' => $state,
                    'scope' => $scope,
                ]);

                $response->getHeaders()->addHeaderLine('Location', $authUrl);
                $response->setStatusCode(302);
                return $response;
            }

            // Otherwise, process Callback logic
            // Stateless CSRF validation — verify HMAC-signed state token
            $this->verifyOAuthState($state, $sm);

            // Verify Apple ID Token securely
            $payload = $this->verifyAppleIdToken($idToken, $clientId);
            $email = $payload['email'];
            $providerId = $payload['sub'];

            $name = '';
            if (!empty($userParam)) {
                $userData = json_decode($userParam, true);
                if (isset($userData['name'])) {
                    $name = trim(($userData['name']['firstName'] ?? '') . ' ' . ($userData['name']['lastName'] ?? ''));
                }
            }

            $clientParam = $request->getPost('client') ?? $request->getQuery('client') ?? 'web';
            if (empty($clientParam) && !empty($state)) {
                $stateData = json_decode($state, true);
                if (isset($stateData['client'])) {
                    $clientParam = $stateData['client'];
                }
            }
            $authResponse = $this->apiAuthService->authenticateSocial($email, $name, 'apple', $providerId, $userIp, $userAgent, null, $clientParam);

            // Set Cookie if client is web
            if ($clientParam === 'web') {
                $response->getHeaders()->addHeaderLine('Set-Cookie', 'refresh_token=' . $authResponse['refresh_token'] . '; Max-Age=2592000; Path=/auth/ipa/refresh; HttpOnly; Secure; SameSite=Lax');
            }

            // Get Apple configuration for frontend redirect
            $frontendRedirect = $appleConfig['frontend_redirect_url'] ?? '';
            if (!empty($frontendRedirect)) {
                $redirectParams = [
                    'token' => $authResponse['token'],
                    'fullname' => $authResponse['fullname']
                ];
                if (isset($authResponse['refresh_token'])) {
                    $redirectParams['refresh_token'] = $authResponse['refresh_token'];
                } else {
                    $redirectParams['refresh_token'] = $authResponse['token_id'];
                }
                $redirectUrl = $frontendRedirect . '?' . http_build_query($redirectParams);
                $response->getHeaders()->addHeaderLine('Location', $redirectUrl);
                $response->setStatusCode(302);
                return $response;
            }

            $response->setStatusCode(200);
            $responseData = [
                'success' => true,
                'schema' => 'Bearer',
                'expires_in' => $authResponse['expire'],
                'token' => $authResponse['token'],
                'luhn_token' => $authResponse['token_id'],
                'user' => [
                    'fullname' => $authResponse['fullname'],
                    'email' => $authResponse['email'],
                    'role' => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid' => $authResponse['uuid'],
                    'wallet' => intval($authResponse['wallet']),
                    'profile_pic' => $authResponse['profile_pic'] ?? null
                ]
            ];
            if (isset($authResponse['refresh_token'])) {
                $responseData['refresh_token'] = $authResponse['refresh_token'];
            }
            $jsonModel->setVariables($responseData);
        } catch (\Throwable $th) {
            $response->setStatusCode(empty($idToken) ? 500 : 400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'AppleCallbackError',
                'description' => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete user account (conforms to App Store requirement)
     *
     * @OA\DELETE(
     *     path="/auth/ipa/delete-user",
     *     tags={"Authentication"},
     *     description="Deactivates the authenticated user account and anonymizes personal data. All active sessions (refresh tokens) are immediately deleted.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Account successfully deleted",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Account successfully deleted")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized (token expired, missing, or invalid)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="Unauthorized"),
     *                     @OA\Property(property="description", type="string", example="token expired")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (e.g. user does not exist or already deactivated)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="DeleteUserError"),
     *                     @OA\Property(property="description", type="string", example="User does not exist")
     *                 )
     *             )
     *         }
     *     )
     * )
     * @OA\POST(
     *     path="/auth/ipa/delete-user",
     *     tags={"Authentication"},
     *     description="Deactivates the authenticated user account (POST alternative)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Account successfully deleted"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="400", description="Bad Request")
     * )
     */
    public function deleteUserAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        if (!$request->isPost() && !$request->isDelete()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'MethodNotAllowed',
                'description' => 'Method Not Allowed. Use DELETE or POST.'
            ]);
            return $jsonModel;
        }

        try {
            // 1. Authenticate the user via the bearer token
            $identity = $this->apiAuthService->getIdentity();
            if (empty($identity['uuid'])) {
                throw new \Exception('Invalid token payload: missing uuid');
            }

            $em = $this->entityManager;

            // 2. Retrieve user entity
            /** @var User $userEntity */
            $userEntity = $em->getRepository(User::class)->findOneBy([
                'uuid' => $identity['uuid']
            ]);

            if ($userEntity === null) {
                throw new \Exception('User does not exist');
            }

            if ($userEntity->getState()->getId() === AuthenticationService::USER_STATE_DISABLED) {
                throw new \Exception('User account is already deleted/disabled');
            }

            // 3. Anonymize/scrub personal data & nullify social IDs to comply with GDPR & App Store rules
            $timestamp = time();
            $userEntity->setFullname('Deleted User ' . $timestamp);
            $userEntity->setEmail('deleted_' . $timestamp . '_' . $userEntity->getEmail());
            $userEntity->setUsername('deleted_' . $timestamp . '_' . $userEntity->getUsername());
            $userEntity->setGoogleId(null);
            $userEntity->setAppleId(null);
            $userEntity->setPassword(AuthenticationService::encryptPassword(bin2hex(random_bytes(16))));

            // 4. Set state to disabled
            $disabledState = $em->find(\Authentication\Entity\UserState::class, AuthenticationService::USER_STATE_DISABLED);
            if ($disabledState !== null) {
                $userEntity->setState($disabledState);
            }

            $em->persist($userEntity);

            // 5. Invalidate and delete all active refresh tokens of this user
            $tokens = $em->getRepository(UserRefreshToken::class)->findBy([
                'userId' => $userEntity->getId()
            ]);
            foreach ($tokens as $token) {
                $em->remove($token);
            }

            $em->flush();

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                'success' => true,
                'description' => 'Account successfully deleted'
            ]);
        } catch (\Authentication\Exceptions\ExpiredAuthDateException $e) {
            $response->setStatusCode(401);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'Unauthorized',
                'description' => 'token expired'
            ]);
        } catch (\Authentication\Exceptions\InvalidTokenException $e) {
            $response->setStatusCode(401);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'Unauthorized',
                'description' => 'invalid_token'
            ]);
        } catch (\Authentication\Exceptions\EmptyTokenException $e) {
            $response->setStatusCode(401);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'Unauthorized',
                'description' => 'empty_token'
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                'success' => false,
                'error' => 'DeleteUserError',
                'description' => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    // -------------------------------------------------------------------------
    // Stateless OAuth CSRF Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a stateless HMAC-SHA256 signed state token for OAuth CSRF protection.
     *
     * Format: base64url( json({ nonce, iat }) ) . '.' . base64url( HMAC-SHA256( payload, signingKey ) )
     *
     * No server-side storage is needed. The signing key is derived from the
     * application's existing JWT signing key so the token is self-verifying.
     *
     * @param  \Psr\Container\ContainerInterface  $sm
     * @return string
     */
    private function generateOAuthState($sm): string
    {
        $baseDir = realpath(__DIR__ . '/../../../../');
        $privateKeyPath = $baseDir . '/data/keys/private.pem';
        if (!file_exists($privateKeyPath)) {
            throw new \Exception('Private key file not found');
        }
        $privateKey = file_get_contents($privateKeyPath);

        $payload = base64_encode(json_encode([
            'nonce' => bin2hex(random_bytes(16)),
            'iat' => time(),
        ]));

        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $mac = base64_encode($signature);

        return $payload . '.' . $mac;
    }

    /**
     * Verify a stateless RSA-SHA256 state token.
     *
     * Throws an exception if the token is missing, structurally invalid,
     * the signature does not match, or the token has expired (TTL: 10 minutes).
     *
     * @param  string|null  $state
     * @param  \Psr\Container\ContainerInterface  $sm
     * @throws \Exception
     */
    private function verifyOAuthState(?string $state, $sm): void
    {
        if (empty($state)) {
            throw new \Exception('CSRF validation failed: state parameter is missing');
        }

        $parts = explode('.', $state, 2);
        if (count($parts) !== 2) {
            throw new \Exception('CSRF validation failed: malformed state token');
        }

        [$payload, $receivedMac] = $parts;

        $baseDir = realpath(__DIR__ . '/../../../../');
        $publicKeyPath = $baseDir . '/data/keys/public.pem';
        if (!file_exists($publicKeyPath)) {
            throw new \Exception('Public key file not found');
        }
        $publicKey = file_get_contents($publicKeyPath);

        $signature = base64_decode($receivedMac);
        $result = openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($result !== 1) {
            throw new \Exception('CSRF validation failed: invalid state signature');
        }

        $data = json_decode(base64_decode($payload), true);
        if (!isset($data['iat'])) {
            throw new \Exception('CSRF validation failed: token payload is malformed');
        }

        // Token TTL: 10 minutes
        if ((time() - (int) $data['iat']) > 600) {
            throw new \Exception('CSRF validation failed: state token has expired');
        }
    }

    /**
     * Generate a UUID v4.
     *
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF));
    }

    /**
     * Verify Google ID Token
     *
     * @param string $idToken
     * @param string $clientId
     * @return array
     * @throws \Exception
     */
    private function verifyGoogleIdToken(string $idToken, string $clientId): array
    {
        $client = new \Laminas\Http\Client();
        $client->setOptions([
            'sslverifypeer' => false,
            'sslverifyhost' => false,
        ]);
        $client->setUri('https://oauth2.googleapis.com/tokeninfo');
        $client->setMethod('GET');
        $client->setParameterGet(['id_token' => $idToken]);
        $res = $client->send();

        if (!$res->isSuccess()) {
            throw new \Exception('Failed to verify Google Token signature or expired token');
        }

        $tokenPayload = json_decode($res->getBody(), true);

        $issuer = $tokenPayload['iss'] ?? '';
        if ($issuer !== 'accounts.google.com' && $issuer !== 'https://accounts.google.com') {
            throw new \Exception('Invalid Google token issuer: ' . $issuer);
        }

        $audience = $tokenPayload['aud'] ?? '';
        if ($audience !== $clientId) {
            throw new \Exception('Invalid Google token audience');
        }

        if (empty($tokenPayload['email'])) {
            throw new \Exception('Google token does not contain email address');
        }

        return $tokenPayload;
    }

    /**
     * Verify Apple ID Token securely
     *
     * @param string $idToken
     * @param string $clientId
     * @return array
     * @throws \Exception
     */
    private function verifyAppleIdToken(string $idToken, string $clientId): array
    {
        $parser = new \Lcobucci\JWT\Token\Parser(new \Lcobucci\JWT\Encoding\JoseEncoder());
        $token = $parser->parse($idToken);
        assert($token instanceof \Lcobucci\JWT\UnencryptedToken);

        $kid = $token->headers()->get('kid');
        if (empty($kid)) {
            throw new \Exception("Apple token missing 'kid' header");
        }

        // Fetch Apple JWKS keys
        $client = new \Laminas\Http\Client();
        $client->setOptions([
            'sslverifypeer' => false,
            'sslverifyhost' => false,
        ]);
        $client->setUri('https://appleid.apple.com/auth/keys');
        $client->setMethod('GET');
        $res = $client->send();
        if (!$res->isSuccess()) {
            throw new \Exception('Failed to fetch Apple public keys');
        }

        $jwks = json_decode($res->getBody(), true);
        if (empty($jwks['keys'])) {
            throw new \Exception('Apple JWKS keys are empty');
        }

        $matchingKey = null;
        foreach ($jwks['keys'] as $key) {
            if ($key['kid'] === $kid) {
                $matchingKey = $key;
                break;
            }
        }
        if (!$matchingKey) {
            throw new \Exception('Apple public key not found for kid: ' . $kid);
        }

        // Convert JWK to PEM
        $pem = $this->jwkToPem($matchingKey);

        // Verify Signature
        $signer = new \Lcobucci\JWT\Signer\Rsa\Sha256();
        $key = \Lcobucci\JWT\Signer\Key\InMemory::plainText($pem);
        $validator = new \Lcobucci\JWT\Validation\Validator();
        try {
            $validator->assert($token, new \Lcobucci\JWT\Validation\Constraint\SignedWith($signer, $key));
        } catch (\Throwable $th) {
            throw new \Exception('Apple token signature verification failed: ' . $th->getMessage());
        }

        // Verify Issuer
        $claims = $token->claims();
        $issuer = $claims->get('iss');
        if ($issuer !== 'https://appleid.apple.com') {
            throw new \Exception('Invalid Apple token issuer: ' . $issuer);
        }

        // Verify Audience (client ID)
        $audience = $claims->get('aud');
        if ($audience !== $clientId) {
            throw new \Exception('Invalid Apple token audience: expected ' . $clientId . ', got ' . $audience);
        }

        // Verify Expiry
        $clock = new \Lcobucci\Clock\SystemClock(new \DateTimeZone('UTC'));
        try {
            $validator->assert($token, new \Lcobucci\JWT\Validation\Constraint\LooseValidAt($clock));
        } catch (\Throwable $th) {
            throw new \Exception('Apple token has expired: ' . $th->getMessage());
        }

        $email = $claims->get('email');
        if (empty($email)) {
            throw new \Exception('Apple token does not contain email');
        }

        return [
            'email' => $email,
            'sub' => $claims->get('sub'),
        ];
    }

    /**
     * Convert JWK public key to PEM format
     *
     * @param array $jwk
     * @return string
     * @throws \Exception
     */
    private function jwkToPem(array $jwk): string
    {
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            throw new \Exception('Invalid JWK: missing n or e components');
        }

        $base64url_decode = function (string $data): string {
            $remainder = strlen($data) % 4;
            if ($remainder) {
                $padlen = 4 - $remainder;
                $data .= str_repeat('=', $padlen);
            }
            return base64_decode(strtr($data, '-_', '+/'));
        };

        $modulus = $base64url_decode($jwk['n']);
        $exponent = $base64url_decode($jwk['e']);

        $encodeLength = function ($length) {
            if ($length <= 0x7F) {
                return chr($length);
            }
            $temp = ltrim(pack('N', $length), chr(0));
            return chr(0x80 | strlen($temp)) . $temp;
        };

        $encodeInteger = function ($data) use ($encodeLength) {
            if (ord($data[0]) & 0x80) {
                $data = chr(0x0) . $data;
            }
            return chr(0x2) . $encodeLength(strlen($data)) . $data;
        };

        $rsaSequence = chr(0x30) . $encodeLength(
            strlen($encodeInteger($modulus))
            + strlen($encodeInteger($exponent))
        ) . $encodeInteger($modulus) . $encodeInteger($exponent);

        $oidRsa = chr(0x6) . chr(0x9) . chr(0x2A) . chr(0x86) . chr(0x48) . chr(0x86) . chr(0xF7) . chr(0xD) . chr(0x1) . chr(0x1) . chr(0x1);
        $algorithmIdentifier = chr(0x30) . $encodeLength(strlen($oidRsa) + 2) . $oidRsa . chr(0x5) . chr(0x0);

        $subjectPublicKey = chr(0x3) . $encodeLength(strlen($rsaSequence) + 1) . chr(0x0) . $rsaSequence;

        $der = chr(0x30) . $encodeLength(strlen($algorithmIdentifier) + strlen($subjectPublicKey)) . $algorithmIdentifier . $subjectPublicKey;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    public function setGoogleAuthService($googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
        return $this;
    }

    public function getGoogleAuthService()
    {
        return $this->googleAuthService;
    }

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
