<?php

namespace Authentication\Controller;

use Authentication\Entity\User;
use Authentication\Service\AuthMailtrapService;
use Authentication\Entity\UserRefreshToken;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Form\InputFilter\LoginInputFilter;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthenticationService;
use General\Service\GeneralService;
use Authentication\Service\RegisterService;
use Doctrine\ORM\EntityManager;
use Laminas\InputFilter\InputFilter;
use General\Service\Mailtrap\MailtrapService;
use Authentication\Service\JWTIssuer;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;
use General\Service\Postmark\AuthenticationEmailService;

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


    public function indexAction()
    {
    }

    /**
     * This API is used to authenticate the user
     * @OA\POST( path="/auth/ipa/login", tags={"Authentication"}, description="The  authenticate connecting entities.You need to be authenticated and be authorized to access the rest endpoints for integration. To authenticate, need to make a request for a token.This token is then added to the authorization header of the request you send to the api endpoint. the granst_type must be client_credentials",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"username", "password", "user_agent", "user_ip"},
     * @OA\Property(property="username", type="string", example="ezekiel_a@yahoo.com", description="This could be either email or phone number "),
     * @OA\Property(property="password", type="string", example="Oluwaseun1"),
     * @OA\Property(property="user_agent", type="string", example="AppleWebKit/535.19 (KHTML, like Gecko)"),
     * @OA\Property(property="user_ip", type="string", example="127.0.0.1"),
     *
     * )
     * ),
     * ),
     *
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
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
     *                         description="Information about the error"
     *                     ),
     *
     *
     *                     example={
     *                         "success": false,
     *                         "message": "Something went wrong",
     *
     *                     }
     *                 )
     *             )
     *         }),
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */
    public function loginAction()
    {
        // var_dump(GeneralService::generateKey(32));

        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();
        if ($request->isPost()) {
            // $json = file_get_contents('php://input');
            $json = $request->getContent();
            // Converts it into a PHP object
            $postData = json_decode($json, true);
            // $postData = (array) $postData;
            // $this->loginInputFilter->setData($postData);
            $errorMessageContainer = new Container("error_code");
            try {
                // Authenticate here
                /**
                 * @var ApiAuthenticateService
                 */
                $authResponse = $this->apiAuthService->setPost($postData)->authenticate();
                $response->getHeaders()->addHeader($authResponse["cookie"]);
                $response->setStatusCode(200);
                $jsonModel->setVariables([
                    "success"       => true,
                    "schema"        => "Bearer",
                    "expires_in"    => $authResponse["expire"],
                    "token"         => $authResponse["token"],
                    "refresh_token" => $authResponse["refresh_token"],  // opaque refresh token (also in HttpOnly cookie)
                    "luhn_token"    => $authResponse["token_id"],
                    "user" => [
                        "fullname" => $authResponse["fullname"],
                        "email"    => $authResponse["email"],
                        "role"     => $authResponse["role"],
                        "username" => $authResponse["username"],
                        "uuid"     => $authResponse["uuid"],
                        "wallet"   => intval($authResponse["wallet"])
                    ]
                ]);
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    "success"     => false,
                    "description" => $th->getMessage(),
                    "data"        => $th->getTrace()
                ]);

                $response->setStatusCode($errorMessageContainer->code);
            }
        }

        return $jsonModel;
    }

    // =========================================================================
    // Token Refresh
    // =========================================================================

    /**
     * Exchange a valid refresh token for a new access token + rotated refresh token.
     *
     * @OA\POST(
     *     path="/auth/ipa/refresh",
     *     tags={"Authentication"},
     *     description="Exchange a valid refresh token for a new access token and a rotated refresh token. The old refresh token is immediately invalidated (token rotation). Send the refresh token in the Authorization header as `Bearer <refresh_token>`, or pass it in the JSON body.",
     *     @OA\RequestBody(
     *         required=false,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="refresh_token", type="string", description="Refresh token issued at login. Can also be sent via Authorization: Bearer header.")
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
     *                     @OA\Property(property="success",       type="boolean"),
     *                     @OA\Property(property="schema",        type="string",  example="Bearer"),
     *                     @OA\Property(property="expires_in",    type="integer", example=1800),
     *                     @OA\Property(property="token",         type="string",  description="New access token"),
     *                     @OA\Property(property="refresh_token", type="string",  description="New rotated refresh token — store this and discard the old one"),
     *                     @OA\Property(property="luhn_token",    type="string")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="401", description="Invalid or expired refresh token"),
     *     @OA\Response(response="400", description="Error")
     * )
     */
    public function refreshAction()
    {
        $request   = $this->getRequest();
        $response  = $this->getResponse();
        $jsonModel = new JsonModel();

        try {
            // Accept refresh token from: 1) Authorization header, 2) JSON body, 3) HttpOnly cookie
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
                // Fall back to HttpOnly cookie
                $cookies = $request->getCookie();
                if ($cookies && $cookies->offsetExists(ApiAuthenticateService::COOKIE_NAME)) {
                    $refreshToken = $cookies->offsetGet(ApiAuthenticateService::COOKIE_NAME);
                }
            }

            if (empty($refreshToken)) {
                throw new \Exception('Refresh token is missing');
            }

            $authResponse = $this->apiAuthService->exchangeRefreshToken($refreshToken);

            // Set the new rotated refresh token as HttpOnly cookie
            $response->getHeaders()->addHeader($authResponse['cookie']);
            $response->setStatusCode(200);
            $jsonModel->setVariables([
                'success'       => true,
                'schema'        => 'Bearer',
                'expires_in'    => $authResponse['expire'],
                'token'         => $authResponse['token'],
                'refresh_token' => $authResponse['refresh_token'],
                'luhn_token'    => $authResponse['token_id'],
                'user' => [
                    'fullname' => $authResponse['fullname'],
                    'email'    => $authResponse['email'],
                    'role'     => $authResponse['role'],
                    'username' => $authResponse['username'],
                    'uuid'     => $authResponse['uuid'],
                    'wallet'   => intval($authResponse['wallet'])
                ]
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(401);
            $jsonModel->setVariables([
                'success'     => false,
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
     *     description="Revokes the supplied refresh token so it can never be used again. Send the refresh token in the Authorization header as `Bearer <refresh_token>`, or pass it in the JSON body.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="refresh_token", type="string")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="Logged out successfully"),
     *     @OA\Response(response="400", description="Error")
     * )
     */
    public function logoutAction()
    {
        $request   = $this->getRequest();
        $response  = $this->getResponse();
        $jsonModel = new JsonModel();

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
            $clearCookie = new \Laminas\Http\Header\SetCookie(
                ApiAuthenticateService::COOKIE_NAME, '', time() - 3600, '/', null, true, true
            );
            $response->getHeaders()->addHeader($clearCookie);
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
     * @OA\POST( path="/auth/ipa/register", tags={"Authentication"}, description="This registeres a user",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"fullname", "username", "email",  "password", "comfirm_password", "address_longitude", "address_google_place_id",  "userAgent", "userIp", "device_type"},
     * @OA\Property(property="fullname", type="string", example="Idowu Yusuf Chukwuma"),
     * @OA\Property(property="username", type="string", example="09012121212"),
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     * @OA\Property(property="password", type="string", example="Oluwaseun1"),
     * @OA\Property(property="address", type="string", example="15 Jacob adeleye street"),
     * @OA\Property(property="address_google_place_id", type="string", example="erriindhikpsfjuhkjnooifjni3", description="Google place Id extracted from the google maps autocomplate functionality"),
     * @OA\Property(property="address_longitude", type="string", example="3.4556666", description="address up longitude"),
     * @OA\Property(property="address_latitude", type="string", example="1.45322", description="address latitude"),
     * @OA\Property(property="confirm_password", type="string", example="Oluwaseun1"),
     * @OA\Property(property="userIp", type="string", example="127.0.0.1"),
     * @OA\Property(property="device_type", type="string", example="mobile", description="<p><ul><li>web</li> <li>mobile</li> <li>others</li></ul></p> "),
     * )
     * ),
     * ),
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         description="The type  of Authentication "
     *                     ),
     *
     *
     *                    @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         description="The Bearer token"
     *                     ),
     *
     *                     example={
     *                         "success": true,
     *                         "data": {
     *                            "fullname": "Idowu Yusuf Chukwuma",
     *                            "email": "ezekiel@yahoo.com"
     *                          },
     *                          "description": "Successfully Created Idowu Yusuf Chukwuma,  profile, please vist Email to confirm email"
     *
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
     *
     *
     *                     example={
     *                         "success": false,
     *                         "message": "Something went wrong",
     *
     *                     }
     *                 )
     *             )
     *         }),
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */


    public function registerAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = file_get_contents('php://input');


            // Converts it into a PHP object
            $postData = (array) json_decode($json, true);

            try {
                //code...
                $responseData = $this->registerService->register($postData);
                if (!is_null($responseData)) {
                    $response->setStatusCode(201);

                    $jsonModel->setVariables([
                        "success" => true,
                        "data" => [
                            "fullname" => $responseData["fullname"],
                            "email" => $responseData["email"],
                        ],
                        "description" => "Successfully Created {$responseData['fullname']},  profile, please vist Email to confirm email"
                    ]);
                }
            } catch (\Throwable $th) {
                //throw $th;
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $th->getMessage(),
                    // "errors" => $
                ]);

                $response->setStatusCode(400);
            }
        }

        return $jsonModel;
    }

    /**
     * This API is used to verity users email
     * @OA\POST( path="/auth/ipa/verify", tags={"Authentication"}, description="Verify user Email",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"code", "email"},
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     * @OA\Property(property="code", type="string", example="345634"),
     * )
     * ),
     * ),
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *                         "success": true,
     *
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
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */
    public function verifyAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        // var_dump("Here");
        if ($request->isPost()) {
            $son = $request->getContent();
            // var_dump($son);
            $postData = json_decode($son, true);
            // var_dump($postData);
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
                        "success" => true
                    ]);
                } catch (\Throwable $th) {
                    return $jsonModel->setVariables([
                        "success" => false,
                        "error" => "Logic",
                        "description" => $th->getMessage()
                    ]);
                }
            } else {
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Validation Error",
                    "description" => $inputFilter->getMessages()
                ]);
                $response = $this->getResponse();
                $response->setStatusCode(400);
            }
        }
        return $jsonModel;
    }


    /**
     * Request another confimation code
     * @OA\POST( path="/auth/ipa/resend-mobile-code", tags={"Authentication"}, description="Use this endpoint to request another account confimation code",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"email"},
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     *
     * )
     * ),
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *                         "success": true,
     *
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
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */
    public function resendMobileCodeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;
        if ($request->isPost()) {
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
                    $mailData["email"] = $data["email"];
                    $mailData["code"] = RegisterService::generateMobileCode();
                    /**
                     * @var User
                     *
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "email" => $data["email"]
                    ]);
                    $userEntity->setUpdatedOn(new \Datetime())->setMobileActivateCode($mailData["code"]);
                    $em->persist($userEntity);
                    $em->flush();
                    $this->mailtrapService->confirmEmail($mailData);
                    $jsonModel->setVariables([
                        "success" => true
                    ]);
                } catch (\Throwable $th) {
                    $response->setStatusCode(400);
                    $jsonModel->setVariables([
                        "success" => false,
                        "error" => "Process Error",
                        "description" => "System culd not resend the code"
                    ]);
                }
            } else {
                $jsonModel->setVariables([
                    "error" => "Validation Error",
                    "description" => $inputFilter->getMessages()
                ]);

                $response->setStatusCode(400);
            }
        }
        return $jsonModel;
    }




    /**
     *
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
            $token_id = $refreshData["token_id"];
            $user_uuid = $refreshData["uuid"];
            /**
             * @var UserRefreshToken
             *
             */
            $refreshTokenEntity = $this->entityManager->getRepository(UserRefreshToken::class)->findOneBy([
                "tokenId" => $token_id
            ]);
            $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
                "uuid" => $user_uuid
            ]);
            $authResponse = "";
            if ($refreshTokenEntity != null) {
                $authResponse = $api_auth->generateRefreshToken($refreshTokenEntity, $userEntity);
                $response->setStatusCode(201);
                $jsonModel->setVariables([
                    "success" => true,
                    "schema" => "Bearer",
                    "expires_in" => $authResponse["expire"],
                    "token" => $authResponse["token"],
                    "luhn_token" => $authResponse["token_id"], // luhn algorithm value
                ]);
            }
        } catch (\Throwable $th) {
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage(),
                // "data" => $th->getTrace()
            ]);

            $response->setStatusCode(400);

            //
        }
        return $jsonModel;
    }


    /**
     * Used to initiate a new passcode and reset password
     * @OA\POST( path="/auth/ipa/intitiate-change-pasword", tags={"Authentication"}, description="Used to initiate a new passcode and reset password",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"email"},
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     *
     *
     * )
     * ),
     * ),
     * security={{"bearerAuth":{}}},
     *  @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *                         "success": true,
     *
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
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     *  TODO initiate change passord
     */
    public function intitiateChangePaswordAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
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
                    /**
                     * @var User
                     *
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "email" => $values["email"]
                    ]);
                    if ($userEntity == null) {
                        throw new \Throwable("User does not exist");
                    }
                    $mailData["to"] = $userEntity->getEmail();
                    $mailData["code"] =

                        $mailData["to"] = $userEntity->getEmail();
                    $mailData["subject"] = "Recyclepoint Reset Password";
                    $mailData["toName"] = $userEntity->getFullname();
                    // $mailData["template"] = "reset-password-mail";
                    $mailData["fulllink"] = $userEntity->getMobileActivateCode();
                   
                    $this->authPostmarkService->resetpassword($mailData);
                    // $this->postmarkService->resetPassword($mailData);

                    // $this->authMailtrapService->sendMobileVerifyCode($mailData);

                    $jsonModel->setVariables([
                        "success" => true
                    ]);
                    $response->setStatusCode(200);
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "description" => $th->getMessage()
                    ]);
                    $response->setStatusCode(400);
                }
            }
        } else {
            // input filter error
            $jsonModel->setVariables([
                "success" => false,
                "description" => $inputFilter->getMessages()
            ]);
            $response->setStatusCode(400);
        }
        return $jsonModel;
    }


    /**
     * Used to confirm code sent to the email for reseting password
     * @OA\POST( path="/auth/ipa/confirmnew-code", tags={"Authentication"}, description="Used to confirm code sent to the email for resetting pasword ",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"code", "email"},
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     * @OA\Property(property="code", type="string", example="340958"),
     *
     * )
     * ),
     * ),
     * security={{"bearerAuth":{}}},
     *  @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *                     @OA\Property(
     *                         property="reset_code",
     *                         type="string",
     *                         description="Reset Code"
     *                     ),
     *
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         description="Information about the request"
     *                     ),
     *
     *                     example={
     *                         "success": true,
     *                       "reset_code":123456,
     *                      "description":"Code Confirmed"
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
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */
    public function confirmnewCodeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData  = json_decode($json, true);
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
                                'isEmpty' => 'Email is required'
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
                    /**
                     * @var User
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "email" => $values["email"]
                    ]);
                    if ($userEntity == null) {
                        throw new \Throwable("User does not exist");
                    }
                    if ($values["code"] == $userEntity->getMobileActivateCode()) {
                        $userEntity->setMobileActivateCode($newCode)->setUpdatedOn(new \Datetime());

                        $em->persist($userEntity);
                        $em->flush();
                        $response->setStatusCode(201);
                        $jsonModel->setVariables([
                            "success" => true,
                            "reset_code" => $userEntity->getMobileActivateCode(),
                            "description" => "Code Confirmed"
                        ]);
                        return $jsonModel;
                    }else{
                        throw new \Exception("Invalid Code");
                    }
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "message" => $th->getMessage()
                    ]);
                    $response->setStatusCode(400);
                }
            } else {
                $jsonModel->setVariables([
                    "success" => false,
                    "message" => $inputFilter->getMessages()
                ]);
                $response->setStatusCode(400);
            }
        }
        // $response->setStatusCode(400);
        return $jsonModel;
    }



    /**
     * Updates the new password
     * @OA\POST( path="/auth/ipa/update-password", tags={"Authentication"}, description="Used to confirm code sent to the email for resetting pasword ",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"password", "email", "confirm_password", "reset_code"},
     * @OA\Property(property="email", type="string", example="ezekiel_a@yahoo.com"),
     * @OA\Property(property="password", type="string", example="gthr!@#ju"),
     * @OA\Property(property="reset_code", type="string", example="123456"),
     * @OA\Property(property="confirm_password", type="string", example="gthr!@#ju"),
     *
     * )
     * ),
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted")
     * )
     *
     * @return void
     */
    public function updatePasswordAction()
    {
        $request = $this->getRequest();
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            $inputFilter = new InputFilter();
            $inputFilter->add([
                "name" => "password",
                "required" => true,
                "allow_empty" => false,
                "filters" => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            // 'max' => 50,
                            "messages" => [
                                StringLength::TOO_SHORT => "The password must be more than 6 characters",
                                StringLength::TOO_LONG => "This password is too long to memorize"
                            ]
                        ]
                    ]
                ]

            ]);
            $inputFilter->add([
                "name" => "email",
                "required" => true,
                "allow_empty" => false,
                "filters" => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            // 'max' => 7,
                            "messages" => [
                                StringLength::TOO_SHORT => "Email is too short",
                                StringLength::TOO_LONG => "This Email is too long to memorize"
                            ]
                        ]
                    ]
                ]

            ]);
            $inputFilter->add([
                "name" => "password",
                "required" => true,
                "allow_empty" => false,
                "filters" => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            // 'max' => 50,
                            "messages" => [
                                StringLength::TOO_SHORT => "The password must be more than 6 characters",
                                StringLength::TOO_LONG => "This password is too long to memorize"
                            ]
                        ]
                    ]
                ]

            ]);
            $inputFilter->add([
                "name" => "confirm_password",
                "required" => true,
                "allow_empty" => false,
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            // 'max' => 50,
                            "messages" => [
                                StringLength::TOO_SHORT => "The password must be more than 6 characters",
                                StringLength::TOO_LONG => "This password is too long to memorize"
                            ]
                        ]
                    ],
                    [
                        'name' => 'Identical',
                        'options' => [
                            'token' => 'password',
                            "messages" => [
                                Identical::NOT_SAME => "The passwords are not identical"
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
                    /**
                     * @var User
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "email" => $values["email"]
                    ]);

                    if ($values["reset_code"] != $userEntity->getMobileActivateCode()) {
                        throw new \Exception("Wrong access code");
                    }
                    if ($userEntity == null) {
                        throw new \Throwable("User does not exist");
                    }

                    $userEntity->setPassword(AuthenticationService::encryptPassword($values["passord"]))->setUpdatedOn(new \Datetime());

                    $em->persist($userEntity);
                    $em->flush();

                    $jsonModel->setVariables([
                        "success" => true
                    ]);
                    $response->setStatusCode(201);
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "description" => $th->getMessage()
                    ]);
                    $response->setStatusCode(400);
                }
            } else {
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $inputFilter->getMessages()
                ]);
                $response->setStatusCode(400);
            }
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
    public function forgotAction()
    {
    }


    public function revokeToken()
    {
        $jsonmodel = new JsonModel([
            // "data"=> var_dump(GeneralService::generateKey(32))
        ]);
        return $jsonmodel;
    }


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
     *     description="Authenticate user with social provider (Google or Apple)",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"provider", "token", "user_agent", "user_ip"},
     *                 @OA\Property(property="provider", type="string", example="google", description="The social identity provider (google or apple)"),
     *                 @OA\Property(property="token", type="string", example="eyJhbGci...", description="The ID Token from Google or Apple SDK"),
     *                 @OA\Property(property="user_agent", type="string", example="AppleWebKit/535.19"),
     *                 @OA\Property(property="user_ip", type="string", example="127.0.0.1")
     *             )
     *         )
     *     ),
     * security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean"),
     *                     @OA\Property(property="schema", type="string"),
     *                     @OA\Property(property="token", type="string")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Error")
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
                    throw new \Exception("Provider and token are required");
                }

                $email = '';
                $name = '';
                $providerId = '';

                if ($provider === 'google') {
                    $client = new \Laminas\Http\Client();
                    $client->setUri('https://oauth2.googleapis.com/tokeninfo');
                    $client->setParameterGet(['id_token' => $idToken]);
                    $res = $client->send();

                    if (!$res->isSuccess()) {
                        throw new \Exception("Failed to verify Google Token");
                    }

                    $payload = json_decode($res->getBody(), true);
                    if (empty($payload['email'])) {
                        throw new \Exception("Google token does not contain email");
                    }
                    $email = $payload['email'];
                    $name = $payload['name'] ?? '';
                    $providerId = $payload['sub'] ?? '';
                } elseif ($provider === 'apple') {
                    $parser = new \Lcobucci\JWT\Token\Parser(new \Lcobucci\JWT\Encoding\JoseEncoder());
                    $token = $parser->parse($idToken);
                    assert($token instanceof \Lcobucci\JWT\UnencryptedToken);
                    
                    $claims = $token->claims();
                    $email = $claims->get('email');
                    if (empty($email)) {
                        throw new \Exception("Apple token does not contain email");
                    }
                    $providerId = $claims->get('sub');
                    $name = ''; // Apple name is only sent once via client SDK OAuth flow
                } else {
                    throw new \Exception("Unsupported provider: " . $provider);
                }

                $authResponse = $this->apiAuthService->authenticateSocial($email, $name, $provider, $providerId, $userIp, $userAgent);
                $response->getHeaders()->addHeader($authResponse["cookie"]);
                $response->setStatusCode(200);

                $jsonModel->setVariables([
                    "success" => true,
                    "schema" => "Bearer",
                    "expires_in" => $authResponse["expire"],
                    "token" => $authResponse["token"],
                    "luhn_token" => $authResponse["token_id"],
                    "user" => [
                        "fullname" => $authResponse["fullname"],
                        "email" => $authResponse["email"],
                        "role" => $authResponse["role"],
                        "username" => $authResponse["username"],
                        "uuid" => $authResponse["uuid"],
                        "wallet" => intval($authResponse["wallet"])
                    ]
                ]);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $th->getMessage(),
                    "data" => $th->getTrace()
                ]);
            }
        } else {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success" => false,
                "description" => "Method Not Allowed"
            ]);
        }

        return $jsonModel;
    }

    /**
     * Google Sign-In Initiate (Stateless CSRF State Generation)
     * @OA\Get(
     *     path="/auth/ipa/google-initiate",
     *     tags={"Authentication"},
     *     description="Generates a stateless HMAC-signed anti-forgery state token and redirects to Google's authorization URL. No server-side session is required — the state is self-verifying.",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="302", description="Redirect to Google authorization page"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     */
    public function googleInitiateAction()
    {
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        try {
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $config = $sm->get('config');
            $googleConfig = $config['google_oauth'] ?? [];

            $clientId    = $googleConfig['client_id'] ?? '';
            $redirectUri = $googleConfig['redirect_uri'] ?? '';
            $scope       = $googleConfig['scope'] ?? 'openid email profile';

            if (empty($clientId) || empty($redirectUri)) {
                throw new \Exception('Google OAuth configuration is incomplete');
            }

            $state = $this->generateOAuthState($sm);

            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'response_type'  => 'code',
                'client_id'      => $clientId,
                'redirect_uri'   => $redirectUri,
                'scope'          => $scope,
                'state'          => $state,
                'access_type'    => 'online',
            ]);

            $response->getHeaders()->addHeaderLine('Location', $authUrl);
            $response->setStatusCode(302);
            return $response;

        } catch (\Throwable $th) {
            $response->setStatusCode(500);
            $jsonModel->setVariables(['success' => false, 'description' => $th->getMessage()]);
            return $jsonModel;
        }
    }

    /**
     * Google OAuth Callback
     * @OA\GET(
     *     path="/auth/ipa/google-callback",
     *     tags={"Authentication"},
     *     description="Google OAuth redirect callback. Validates the HMAC-signed stateless state parameter to prevent CSRF, then exchanges the code for tokens.",
     *     @OA\Parameter(name="code", in="query", required=true, description="Authorization code returned by Google", @OA\Schema(type="string")),
     *     @OA\Parameter(name="state", in="query", required=true, description="Stateless HMAC-signed anti-forgery token", @OA\Schema(type="string")),
     * security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={@OA\MediaType(mediaType="application/json", @OA\Schema(@OA\Property(property="success", type="boolean"), @OA\Property(property="token", type="string")))}
     *     ),
     *     @OA\Response(response="400", description="Error")
     * )
     */
    public function googleCallbackAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        $code  = $request->getQuery('code');
        $state = $request->getQuery('state');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIp    = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            // Stateless CSRF validation — no session needed
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $this->verifyOAuthState($state, $sm);

            if (empty($code)) {
                throw new \Exception("Authorization code is missing");
            }

            $config = $sm->get('config');
            $googleConfig = $config['google_oauth'] ?? [];

            $clientId = $googleConfig['client_id'] ?? '';
            $clientSecret = $googleConfig['client_secret'] ?? '';
            $redirectUri = $googleConfig['redirect_uri'] ?? '';

            if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
                throw new \Exception("Google OAuth configuration is missing on the server");
            }

            // Exchange authorization code for tokens
            $client = new \Laminas\Http\Client();
            $client->setUri('https://oauth2.googleapis.com/token');
            $client->setMethod('POST');
            $client->setParameterPost([
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code'
            ]);
            $res = $client->send();

            if (!$res->isSuccess()) {
                throw new \Exception("Failed to exchange auth code with Google: " . $res->getBody());
            }

            $payload = json_decode($res->getBody(), true);
            $idToken = $payload['id_token'] ?? '';

            if (empty($idToken)) {
                throw new \Exception("Google token exchange did not return ID token");
            }

            // Verify ID Token
            $client->setUri('https://oauth2.googleapis.com/tokeninfo');
            $client->setMethod('GET');
            $client->setParameterGet(['id_token' => $idToken]);
            $res = $client->send();

            if (!$res->isSuccess()) {
                throw new \Exception("Failed to verify Google Token");
            }

            $tokenPayload = json_decode($res->getBody(), true);
            if (empty($tokenPayload['email'])) {
                throw new \Exception("Google token does not contain email");
            }

            $email = $tokenPayload['email'];
            $name = $tokenPayload['name'] ?? '';
            $providerId = $tokenPayload['sub'] ?? '';

            $authResponse = $this->apiAuthService->authenticateSocial($email, $name, 'google', $providerId, $userIp, $userAgent);
            
            // Set Cookie
            $response->getHeaders()->addHeader($authResponse["cookie"]);

            // If a frontend redirect URL is configured, redirect the user
            $frontendRedirect = $googleConfig['frontend_redirect_url'] ?? '';
            if (!empty($frontendRedirect)) {
                $redirectUrl = $frontendRedirect . '?' . http_build_query([
                    'token' => $authResponse['token'],
                    'refresh_token' => $authResponse['token_id'],
                    'fullname' => $authResponse['fullname']
                ]);
                $response->getHeaders()->addHeaderLine('Location', $redirectUrl);
                $response->setStatusCode(302);
                return $response;
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "schema" => "Bearer",
                "expires_in" => $authResponse["expire"],
                "token" => $authResponse["token"],
                "luhn_token" => $authResponse["token_id"],
                "user" => [
                    "fullname" => $authResponse["fullname"],
                    "email" => $authResponse["email"],
                    "role" => $authResponse["role"],
                    "username" => $authResponse["username"],
                    "uuid" => $authResponse["uuid"],
                    "wallet" => intval($authResponse["wallet"])
                ]
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Apple Sign-In Initiate (Stateless CSRF State Generation)
     * @OA\Get(
     *     path="/auth/ipa/apple-initiate",
     *     tags={"Authentication"},
     *     description="Generates a stateless HMAC-signed anti-forgery state token and redirects to Apple's authorization URL. No server-side session is required — the state is self-verifying.",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="302", description="Redirect to Apple authorization page"),
     *     @OA\Response(response="500", description="Server configuration error")
     * )
     */
    public function appleInitiateAction()
    {
        $response = $this->getResponse();
        $jsonModel = new JsonModel();

        try {
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $config = $sm->get('config');
            $appleConfig = $config['apple_oauth'] ?? [];

            $clientId     = $appleConfig['client_id'] ?? '';
            $redirectUri  = $appleConfig['redirect_uri'] ?? '';
            $scope        = $appleConfig['scope'] ?? 'name email';
            $responseMode = $appleConfig['response_mode'] ?? 'form_post';

            if (empty($clientId) || empty($redirectUri)) {
                throw new \Exception('Apple OAuth configuration is incomplete');
            }

            // Generate a stateless HMAC-signed state token — no session stored
            $state = $this->generateOAuthState($sm);

            $authUrl = 'https://appleid.apple.com/auth/authorize?' . http_build_query([
                'response_type' => 'code id_token',
                'response_mode' => $responseMode,
                'client_id'     => $clientId,
                'redirect_uri'  => $redirectUri,
                'state'         => $state,
                'scope'         => $scope,
            ]);

            $response->getHeaders()->addHeaderLine('Location', $authUrl);
            $response->setStatusCode(302);
            return $response;

        } catch (\Throwable $th) {
            $response->setStatusCode(500);
            $jsonModel->setVariables(['success' => false, 'description' => $th->getMessage()]);
            return $jsonModel;
        }
    }

    /**
     * Apple ID Callback
     * @OA\POST(
     *     path="/auth/ipa/apple-callback",
     *     tags={"Authentication"},
     *     description="Apple OAuth redirect callback endpoint (Form Post response mode)",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/x-www-form-urlencoded",
     *                 @OA\Schema(
     *                     @OA\Property(property="code", type="string", description="Authorization code from Apple"),
     *                     @OA\Property(property="id_token", type="string", description="Identity Token (JWT) from Apple"),
     *                     @OA\Property(property="state", type="string", description="State parameter passed in request"),
     *                     @OA\Property(property="user", type="string", description="JSON string with user name/email (first-time login)")
     *                 )
     *             )
     *         }
     *     ),
     * security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean"),
     *                     @OA\Property(property="token", type="string")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Error")
     * )
     * @OA\GET(
     *     path="/auth/ipa/apple-callback",
     *     tags={"Authentication"},
     *     description="Apple OAuth redirect callback endpoint (GET backup)",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=false,
     *         description="Authorization code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id_token",
     *         in="query",
     *         required=false,
     *         description="Identity Token (JWT)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(response="400", description="Error")
     * )
     */
    public function appleCallbackAction()
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
            // Stateless CSRF validation — verify HMAC-signed state token, no session needed
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $this->verifyOAuthState($state, $sm);

            if (empty($idToken)) {
                throw new \Exception('ID token is missing');
            }
            
            // Parse JWT token from Apple
            $parser = new \Lcobucci\JWT\Token\Parser(new \Lcobucci\JWT\Encoding\JoseEncoder());
            $token = $parser->parse($idToken);
            assert($token instanceof \Lcobucci\JWT\UnencryptedToken);
            $claims = $token->claims();
            
            $email = $claims->get('email');
            $providerId = $claims->get('sub');
            
            $name = '';
            if (!empty($userParam)) {
                $userData = json_decode($userParam, true);
                if (isset($userData['name'])) {
                    $name = trim(($userData['name']['firstName'] ?? '') . ' ' . ($userData['name']['lastName'] ?? ''));
                }
            }
            
            if (empty($email)) {
                throw new \Exception("Apple token does not contain email");
            }

            $authResponse = $this->apiAuthService->authenticateSocial($email, $name, 'apple', $providerId, $userIp, $userAgent);
            
            // Set Cookie
            $response->getHeaders()->addHeader($authResponse["cookie"]);

            // Get Apple configuration for frontend redirect
            $sm = $this->getEvent()->getApplication()->getServiceManager();
            $config = $sm->get('config');
            $appleConfig = $config['apple_oauth'] ?? [];
            
            $frontendRedirect = $appleConfig['frontend_redirect_url'] ?? '';
            if (!empty($frontendRedirect)) {
                $redirectUrl = $frontendRedirect . '?' . http_build_query([
                    'token' => $authResponse['token'],
                    'refresh_token' => $authResponse['token_id'],
                    'fullname' => $authResponse['fullname']
                ]);
                $response->getHeaders()->addHeaderLine('Location', $redirectUrl);
                $response->setStatusCode(302);
                return $response;
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "schema" => "Bearer",
                "expires_in" => $authResponse["expire"],
                "token" => $authResponse["token"],
                "luhn_token" => $authResponse["token_id"],
                "user" => [
                    "fullname" => $authResponse["fullname"],
                    "email" => $authResponse["email"],
                    "role" => $authResponse["role"],
                    "username" => $authResponse["username"],
                    "uuid" => $authResponse["uuid"],
                    "wallet" => intval($authResponse["wallet"])
                ]
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage(),
                "data" => $th->getTrace()
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
        $signingKey = $this->getOAuthSigningKey($sm);

        $payload = base64_encode(json_encode([
            'nonce' => bin2hex(random_bytes(16)),
            'iat'   => time(),
        ]));

        $mac = base64_encode(hash_hmac('sha256', $payload, $signingKey, true));

        return $payload . '.' . $mac;
    }

    /**
     * Verify a stateless HMAC-SHA256 state token.
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

        $signingKey  = $this->getOAuthSigningKey($sm);
        $expectedMac = base64_encode(hash_hmac('sha256', $payload, $signingKey, true));

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedMac, $receivedMac)) {
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
     * Derive the HMAC signing key from the application's JWT sign key.
     *
     * @param  \Psr\Container\ContainerInterface  $sm
     * @return string
     */
    private function getOAuthSigningKey($sm): string
    {
        /** @var \Authentication\Service\JWTIssuer $jwtIssuer */
        $jwtIssuer  = $sm->get(\Authentication\Service\JWTIssuer::class);
        $jwtConfig  = $jwtIssuer->getConfig()->getJwtConfigEntity();
        $signKey    = $jwtConfig->getSignKey();

        // The stored key may be base64-encoded; decode and use raw bytes
        $raw = base64_decode($signKey, true);
        return ($raw !== false && strlen($raw) >= 16) ? $raw : $signKey;
    }
}
