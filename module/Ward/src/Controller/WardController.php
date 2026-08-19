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
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"fullname", "date_of_birth"},
     *                     @OA\Property(property="fullname", type="string", example="John Doe Jr.", description="Ward's full name"),
     *                     @OA\Property(property="date_of_birth", type="string", example="2015-08-15", description="Ward's date of birth (YYYY-MM-DD)"),
     *                     @OA\Property(property="unique_identifier", type="string", example="WARD-JDJ-001", description="Optional unique identifier. Auto-generated if not provided."),
     *                     @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045", description="Optional UUID. Auto-generated if not provided.")
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
     *                         @OA\Property(property="unique_identifier", type="string", example="WARD-JDJ-001")
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

        if (!$request->isPost()) {
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
                    "unique_identifier" => $ward->getUniqueIdentifier()
                ],
                "description" => "Successfully registered ward {$ward->getFullname()}."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "WardRegistrationError",
                "description" => $th->getMessage()
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
     *                             @OA\Property(property="unique_identifier", type="string", example="WARD-JDJ-001")
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

        if (!$request->isGet()) {
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
                    "unique_identifier" => $ward->getUniqueIdentifier()
                ];
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $data
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "WardListError",
                "description" => $th->getMessage()
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
     *         description="The ID, UUID, or unique identifier of the ward",
     *         @OA\Schema(type="string")
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
     *                         @OA\Property(property="unique_identifier", type="string", example="WARD-JDJ-001")
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

        if (!$request->isGet()) {
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
                    ?? $this->params()->fromQuery('uuid') 
                    ?? $this->params()->fromQuery('unique_identifier');
            }

            if (empty($id)) {
                throw new \Exception("Ward identifier (id, uuid, or unique_identifier) is required.");
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
                    "unique_identifier" => $ward->getUniqueIdentifier()
                ]
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "WardInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }
}
