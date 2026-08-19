<?php

namespace Dyscalculia\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Dyscalculia\Service\DyscalculiaService;

class DyscalculiaController extends AbstractActionController
{
    /**
     * @var DyscalculiaService
     */
    private $dyscalculiaService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * DyscalculiaController constructor.
     *
     * @param DyscalculiaService $dyscalculiaService
     * @param ApiAuthenticateService $apiAuthService
     */
    public function __construct(DyscalculiaService $dyscalculiaService, ApiAuthenticateService $apiAuthService)
    {
        $this->dyscalculiaService = $dyscalculiaService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Registers a new Dyscalculia assessment.
     *
     * @OA\Post(
     *     path="/api/dyscalculia/register",
     *     tags={"Dyscalculia"},
     *     description="Registers a new Dyscalculia assessment for a ward.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"ward_id", "arithmetic_score", "number_sense_score", "spatial_reasoning_score", "severity_level", "assessment_date"},
     *                     @OA\Property(property="ward_id", type="string", example="1"),
     *                     @OA\Property(property="arithmetic_score", type="integer", example=75),
     *                     @OA\Property(property="number_sense_score", type="integer", example=80),
     *                     @OA\Property(property="spatial_reasoning_score", type="integer", example=70),
     *                     @OA\Property(property="severity_level", type="string", example="Moderate"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-19"),
     *                     @OA\Property(property="notes", type="string", example="Struggles with basic addition."),
     *                     @OA\Property(property="unique_identifier", type="string", example="CAL-DEF-789"),
     *                     @OA\Property(property="uuid", type="string", example="d9cfc81b-53c8-47bc-ad78-cb75a8927495")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="201", description="Created"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
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

            $json = $request->getContent();
            $postData = (array) json_decode($json, true);

            $assessment = $this->dyscalculiaService->register($postData, $identity);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully registered Dyscalculia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyscalculiaRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Lists Dyscalculia assessments.
     *
     * @OA\Get(
     *     path="/api/dyscalculia/list-assessments",
     *     tags={"Dyscalculia"},
     *     description="Retrieve a list of all Dyscalculia assessments for the authenticated user's wards.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function listAssessmentsAction()
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

            $assessments = $this->dyscalculiaService->listAssessments($identity);
            $data = [];
            foreach ($assessments as $assessment) {
                $data[] = $this->mapEntityToArray($assessment);
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
                "error" => "DyscalculiaListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Get details of a Dyscalculia assessment.
     *
     * @OA\Get(
     *     path="/api/dyscalculia/assessment-info/{id}",
     *     tags={"Dyscalculia"},
     *     description="Retrieve details of a specific Dyscalculia assessment by ID, UUID, or unique identifier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function assessmentInfoAction()
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
                throw new \Exception("Assessment identifier (id, uuid, or unique_identifier) is required.");
            }

            $assessment = $this->dyscalculiaService->getAssessmentInfo($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyscalculiaInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update an existing Dyscalculia assessment.
     *
     * @OA\Put(
     *     path="/api/dyscalculia/update/{id}",
     *     tags={"Dyscalculia"},
     *     description="Update an existing Dyscalculia assessment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="arithmetic_score", type="integer", example=80),
     *                     @OA\Property(property="number_sense_score", type="integer", example=85),
     *                     @OA\Property(property="spatial_reasoning_score", type="integer", example=75),
     *                     @OA\Property(property="severity_level", type="string", example="Mild"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-20"),
     *                     @OA\Property(property="notes", type="string", example="Showed improvement.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="Updated"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function updateAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPut()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use PUT."
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
                throw new \Exception("Assessment identifier (id) is required in the path.");
            }

            $json = $request->getContent();
            $putData = (array) json_decode($json, true);

            $assessment = $this->dyscalculiaService->update($id, $putData, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully updated Dyscalculia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyscalculiaUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete an assessment.
     *
     * @OA\Delete(
     *     path="/api/dyscalculia/delete/{id}",
     *     tags={"Dyscalculia"},
     *     description="Delete an existing Dyscalculia assessment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     *
     * @return JsonModel
     */
    public function deleteAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isDelete()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use DELETE."
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
                throw new \Exception("Assessment identifier (id) is required in the path.");
            }

            $this->dyscalculiaService->delete($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted Dyscalculia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyscalculiaDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Map Dyscalculia Entity properties to array.
     */
    private function mapEntityToArray($dyscalculia)
    {
        return [
            "id" => $dyscalculia->getId(),
            "uuid" => $dyscalculia->getUuid(),
            "unique_identifier" => $dyscalculia->getUniqueIdentifier(),
            "ward_id" => $dyscalculia->getWard()->getId(),
            "ward_fullname" => $dyscalculia->getWard()->getFullname(),
            "assessment_date" => $dyscalculia->getAssessmentDate()->format('Y-m-d'),
            "arithmetic_score" => $dyscalculia->getArithmeticScore(),
            "number_sense_score" => $dyscalculia->getNumberSenseScore(),
            "spatial_reasoning_score" => $dyscalculia->getSpatialReasoningScore(),
            "total_score" => $dyscalculia->getTotalScore(),
            "severity_level" => $dyscalculia->getSeverityLevel(),
            "notes" => $dyscalculia->getNotes()
        ];
    }
}
