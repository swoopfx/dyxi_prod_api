<?php

namespace Ward\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Ward\Service\WardService;

class WardController extends AbstractActionController
{
    /**
     * @var WardService
     */
    private $wardService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * WardController constructor.
     *
     * @param WardService $wardService
     * @param ApiAuthenticateService $apiAuthService
     */
    public function __construct(WardService $wardService, ApiAuthenticateService $apiAuthService)
    {
        $this->wardService = $wardService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Registers a Ward.
     *
     * @OA\Post(
     *     path="/api/ward/register",
     *     tags={"Ward"},
     *     description="Registers a new ward for the authenticated user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to register a new ward. Required fields: 'fullname' and 'date_of_birth'.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"fullname", "date_of_birth"},
     *                     description="Ward Registration Schema specifying required and optional input parameters with data formats.",
     *                     @OA\Property(property="fullname", type="string", example="John Doe Jr.", description="[REQUIRED] Ward's full name. Format: String (max 255 chars)."),
     *                     @OA\Property(property="date_of_birth", type="string", format="date", example="2015-08-15", description="[REQUIRED] Ward's date of birth. Format: YYYY-MM-DD (ISO 8601 date)."),
     *                     @OA\Property(property="status", type="string", example="active", enum={"active", "suspended", "pending"}, description="[OPTIONAL] Ward status. Format: String enum ('active', 'suspended', 'pending'). Defaults to 'active'."),
     *                     @OA\Property(property="uuid", type="string", format="uuid", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045", description="[OPTIONAL] Ward UUID identifier. Format: UUID v4 string (8-4-4-4-12 hex). Auto-generated if not provided."),
     *                     @OA\Property(property="expireDate", type="string", example="2026-10-01 12:00:00", description="[OPTIONAL] Date/time when ward account expires or duration in hours."),
     *                     @OA\Property(property="gender", type="string", example="Female", description="[OPTIONAL] Ward gender name or gender ID. Defaults to 'Female'."),
     *                     @OA\Property(property="gender_id", type="integer", example=2, description="[OPTIONAL] Ward gender ID (1=Male, 2=Female, 3=Other). Defaults to 2 (Female).")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Ward registered successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="fullname", type="string", example="John Doe Jr."),
     *                         @OA\Property(property="date_of_birth", type="string", example="2015-08-15"),
     *                         @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="status_id", type="integer", example=1),
     *                         @OA\Property(property="age", type="integer", example=11),
     *                         @OA\Property(property="expireDate", type="integer", example=720),
     *                         @OA\Property(property="gender", type="string", example="Female"),
     *                         @OA\Property(property="gender_id", type="integer", example=2)
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully registered ward John Doe Jr.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request (validation failed or error registering ward)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="WardRegistrationError"),
     *                     @OA\Property(property="description", type="string", example="Full name is required.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="Unauthorized"),
     *                     @OA\Property(property="description", type="string", example="invalid_token")
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
     *
     * @return JsonModel
     */
    public function registerAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (! $request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use POST."
            ]);
            return $jsonModel;
        }

        $json = $request->getContent();
        $postData = (array) json_decode($json, true);

        try {
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $ward = $this->wardService->register($postData, $identity);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => [
                    "id" => $ward->getId(),
                    "fullname" => $ward->getFullname(),
                    "date_of_birth" => $ward->getDateOfBirth()->format('Y-m-d'),
                    "uuid" => $ward->getUuid(),
                    "status" => $ward->getStatus() ? $ward->getStatus()->getStatus() : null,
                    "status_id" => $ward->getStatus() ? $ward->getStatus()->getId() : null,
                    "age" => $ward->getAge(),
                    "expireDate" => $ward->getExpireHours(),
                    "gender" => $ward->getGender() ? $ward->getGender()->getGender() : null,
                    "gender_id" => $ward->getGender() ? $ward->getGender()->getId() : null
                ],
                "description" => "Successfully registered ward {$ward->getFullname()}."
            ]);
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            $statusCode = str_contains($message, 'Unauthorized') ? 401 : (str_contains($message, 'permission') || str_contains($message, 'Access denied') ? 403 : 400);
            $errorType = ($statusCode === 401) ? "Unauthorized" : (($statusCode === 403) ? "Forbidden" : "WardRegistrationError");
            $response->setStatusCode($statusCode);
            $jsonModel->setVariables([
                "success" => false,
                "error" => $errorType,
                "description" => $message
            ]);
        }

        return $jsonModel;
    }

    /**
     * Lists Wards.
     *
     * @OA\Get(
     *     path="/api/ward/list-ward",
     *     tags={"Ward"},
     *     description="Retrieve a list of all wards registered under the authenticated user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="fullname", type="string", example="John Doe Jr."),
     *                             @OA\Property(property="date_of_birth", type="string", example="2015-08-15"),
     *                             @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                             @OA\Property(property="status", type="string", example="active"),
     *                             @OA\Property(property="status_id", type="integer", example=1),
     *                             @OA\Property(property="age", type="integer", example=11),
     *                             @OA\Property(property="expireDate", type="integer", example=720),
     *                             @OA\Property(property="gender", type="string", example="Female"),
     *                             @OA\Property(property="gender_id", type="integer", example=2)
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function listWardAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (! $request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $wards = $this->wardService->listWards($identity);
            $data = [];
            foreach ($wards as $ward) {
                $data[] = [
                    "id" => $ward->getId(),
                    "fullname" => $ward->getFullname(),
                    "date_of_birth" => $ward->getDateOfBirth()->format('Y-m-d'),
                    "uuid" => $ward->getUuid(),
                    "status" => $ward->getStatus() ? $ward->getStatus()->getStatus() : null,
                    "status_id" => $ward->getStatus() ? $ward->getStatus()->getId() : null,
                    "age" => $ward->getAge(),
                    "expireDate" => $ward->getExpireHours(),
                    "gender" => $ward->getGender() ? $ward->getGender()->getGender() : null,
                    "gender_id" => $ward->getGender() ? $ward->getGender()->getId() : null
                ];
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            $statusCode = str_contains($message, 'Unauthorized') ? 401 : (str_contains($message, 'permission') || str_contains($message, 'Access denied') ? 403 : 400);
            $errorType = ($statusCode === 401) ? "Unauthorized" : (($statusCode === 403) ? "Forbidden" : "WardListError");
            $response->setStatusCode($statusCode);
            $jsonModel->setVariables([
                "success" => false,
                "error" => $errorType,
                "description" => $message
            ]);
        }

        return $jsonModel;
    }

    /**
     * Ward Info.
     *
     * @OA\Get(
     *     path="/api/ward/ward-info/{id}",
     *     tags={"Ward"},
     *     description="Retrieve details of a specific ward by ID, UUID, or unique identifier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="[REQUIRED] The integer ID or UUID string of the ward. Format: Integer ID (e.g. 1) or UUID string (e.g. 7b7f1ad9-d9d5-451e-8ef9-eb9915159045).",
     *         @OA\Schema(type="string", description="Identifier parameter. Format: String representing integer ID or UUID.")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="fullname", type="string", example="John Doe Jr."),
     *                         @OA\Property(property="date_of_birth", type="string", example="2015-08-15"),
     *                         @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="status_id", type="integer", example=1),
     *                         @OA\Property(property="age", type="integer", example=11),
     *                         @OA\Property(property="expireDate", type="integer", example=720),
     *                         @OA\Property(property="gender", type="string", example="Female"),
     *                         @OA\Property(property="gender_id", type="integer", example=2)
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request (ward not found)"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function wardInfoAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (! $request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $id = $this->params()->fromRoute('id');
            if (empty($id)) {
                $id = $this->params()->fromQuery('id')
                    ?? $this->params()->fromQuery('uuid');
            }

            if (empty($id)) {
                throw new \Exception("Ward identifier (id or uuid) is required.");
            }

            $ward = $this->wardService->getWardInfo($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => [
                    "id" => $ward->getId(),
                    "fullname" => $ward->getFullname(),
                    "date_of_birth" => $ward->getDateOfBirth()->format('Y-m-d'),
                    "uuid" => $ward->getUuid(),
                    "status" => $ward->getStatus() ? $ward->getStatus()->getStatus() : null,
                    "status_id" => $ward->getStatus() ? $ward->getStatus()->getId() : null,
                    "age" => $ward->getAge(),
                    "expireDate" => $ward->getExpireHours(),
                    "gender" => $ward->getGender() ? $ward->getGender()->getGender() : null,
                    "gender_id" => $ward->getGender() ? $ward->getGender()->getId() : null
                ]
            ]);
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            $statusCode = str_contains($message, 'Unauthorized') ? 401 : (str_contains($message, 'permission') || str_contains($message, 'Access denied') ? 403 : 400);
            $errorType = ($statusCode === 401) ? "Unauthorized" : (($statusCode === 403) ? "Forbidden" : "WardInfoError");
            $response->setStatusCode($statusCode);
            $jsonModel->setVariables([
                "success" => false,
                "error" => $errorType,
                "description" => $message
            ]);
        }

        return $jsonModel;
    }
}
