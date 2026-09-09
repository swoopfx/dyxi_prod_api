<?php

namespace Adhd\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Adhd\Service\AdhdService;

class AdhdController extends AbstractActionController
{
    /**
     * @var AdhdService
     */
    private $adhdService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * AdhdController constructor.
     *
     * @param AdhdService $adhdService
     * @param ApiAuthenticateService $apiAuthService
     */
    public function __construct(AdhdService $adhdService, ApiAuthenticateService $apiAuthService)
    {
        $this->adhdService = $adhdService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Registers a new ADHD assessment.
     *
     * @OA\Post(
     *     path="/api/adhd/register",
     *     tags={"ADHD"},
     *     description="Registers a new ADHD assessment for a ward.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to register a new ADHD assessment. Required fields: 'ward_id', 'inattention_score', 'hyperactivity_score', 'diagnosis', 'assessment_date'.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"ward_id", "inattention_score", "hyperactivity_score", "diagnosis", "assessment_date"},
     *                     description="ADHD Assessment Registration Schema identifying required and optional parameters with data types and formats.",
     *                     @OA\Property(property="ward_id", type="string", example="1", description="[REQUIRED] Database ID or UUID string of target Ward. Format: String or Integer."),
     *                     @OA\Property(property="inattention_score", type="integer", example=14, description="[REQUIRED] Inattention sub-score. Format: Integer."),
     *                     @OA\Property(property="hyperactivity_score", type="integer", example=12, description="[REQUIRED] Hyperactivity sub-score. Format: Integer."),
     *                     @OA\Property(property="diagnosis", type="string", example="Combined Type", description="[REQUIRED] Diagnosis result text. Format: String."),
     *                     @OA\Property(property="assessment_date", type="string", format="date", example="2026-08-19", description="[REQUIRED] Assessment date. Format: YYYY-MM-DD (ISO 8601 date)."),
     *                     @OA\Property(property="notes", type="string", example="Needs school accommodations.", description="[OPTIONAL] Additional assessment notes. Format: String."),
     *                     @OA\Property(property="unique_identifier", type="string", example="ADH-XYZ-456", description="[OPTIONAL] Unique identifier. Format: String (max 255 chars). Auto-generated if omitted."),
     *                     @OA\Property(property="uuid", type="string", format="uuid", example="e25f828a-784c-47eb-ba68-c1a7428807d4", description="[OPTIONAL] UUID v4 string. Format: UUID (8-4-4-4-12 hex). Auto-generated if omitted.")
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

        if (! $request->isPost()) {
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

            $assessment = $this->adhdService->register($postData, $identity);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully registered ADHD assessment."
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "AdhdRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Lists ADHD assessments.
     *
     * @OA\Get(
     *     path="/api/adhd/list-assessments",
     *     tags={"ADHD"},
     *     description="Retrieve a list of all ADHD assessments for the authenticated user's wards.",
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

            $assessments = $this->adhdService->listAssessments($identity);
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
                "error" => "AdhdListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Get details of an ADHD assessment.
     *
     * @OA\Get(
     *     path="/api/adhd/assessment-info/{id}",
     *     tags={"ADHD"},
     *     description="Retrieve details of a specific ADHD assessment by ID, UUID, or unique identifier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="[REQUIRED] Integer ID, UUID, or unique identifier of the assessment. Format: Integer, UUID, or String.",
     *         @OA\Schema(type="string", description="Assessment identifier parameter.")
     *     ),

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
                    ?? $this->params()->fromQuery('uuid')
                    ?? $this->params()->fromQuery('unique_identifier');
            }

            if (empty($id)) {
                throw new \Exception("Assessment identifier (id, uuid, or unique_identifier) is required.");
            }

            $assessment = $this->adhdService->getAssessmentInfo($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment)
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "AdhdInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update an existing ADHD assessment.
     *
     * @OA\Put(
     *     path="/api/adhd/update/{id}",
     *     tags={"ADHD"},
     *     description="Update an existing ADHD assessment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="[REQUIRED] Integer ID, UUID, or unique identifier of the assessment to update. Format: Integer, UUID, or String.",
     *         @OA\Schema(type="string", description="Assessment identifier parameter.")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update an ADHD assessment. All properties are optional.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     description="ADHD Assessment Update Schema specifying optional update properties and data formats.",
     *                     @OA\Property(property="inattention_score", type="integer", example=16, description="[OPTIONAL] Updated inattention sub-score. Format: Integer."),
     *                     @OA\Property(property="hyperactivity_score", type="integer", example=10, description="[OPTIONAL] Updated hyperactivity sub-score. Format: Integer."),
     *                     @OA\Property(property="diagnosis", type="string", example="Inattentive Type", description="[OPTIONAL] Updated diagnosis text. Format: String."),
     *                     @OA\Property(property="assessment_date", type="string", format="date", example="2026-08-20", description="[OPTIONAL] Updated assessment date. Format: YYYY-MM-DD (ISO 8601 date)."),
     *                     @OA\Property(property="notes", type="string", example="Therapy schedule updated.", description="[OPTIONAL] Updated assessment notes. Format: String.")
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

        if (! $request->isPut()) {
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

            $assessment = $this->adhdService->update($id, $putData, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully updated ADHD assessment."
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "AdhdUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete an assessment.
     *
     * @OA\Delete(
     *     path="/api/adhd/delete/{id}",
     *     tags={"ADHD"},
     *     description="Delete an existing ADHD assessment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="[REQUIRED] Integer ID, UUID, or unique identifier of the assessment to delete. Format: Integer, UUID, or String.",
     *         @OA\Schema(type="string", description="Assessment identifier parameter.")
     *     ),

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

        if (! $request->isDelete()) {
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

            $this->adhdService->delete($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted ADHD assessment."
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "AdhdDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Map ADHD Entity properties to array.
     */
    private function mapEntityToArray($adhd)
    {
        return [
            "id" => $adhd->getId(),
            "uuid" => $adhd->getUuid(),
            "unique_identifier" => $adhd->getUniqueIdentifier(),
            "ward_id" => $adhd->getWard()->getId(),
            "ward_fullname" => $adhd->getWard()->getFullname(),
            "assessment_date" => $adhd->getAssessmentDate()->format('Y-m-d'),
            "inattention_score" => $adhd->getInattentionScore(),
            "hyperactivity_score" => $adhd->getHyperactivityScore(),
            "total_score" => $adhd->getTotalScore(),
            "diagnosis" => $adhd->getDiagnosis(),
            "notes" => $adhd->getNotes()
        ];
    }
}
