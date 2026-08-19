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
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"ward_id", "inattention_score", "hyperactivity_score", "diagnosis", "assessment_date"},
     *                     @OA\Property(property="ward_id", type="string", example="1"),
     *                     @OA\Property(property="inattention_score", type="integer", example=14),
     *                     @OA\Property(property="hyperactivity_score", type="integer", example=12),
     *                     @OA\Property(property="diagnosis", type="string", example="Combined Type"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-19"),
     *                     @OA\Property(property="notes", type="string", example="Needs school accommodations."),
     *                     @OA\Property(property="unique_identifier", type="string", example="ADH-XYZ-456"),
     *                     @OA\Property(property="uuid", type="string", example="e25f828a-784c-47eb-ba68-c1a7428807d4")
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="inattention_score", type="integer", example=16),
     *                     @OA\Property(property="hyperactivity_score", type="integer", example=10),
     *                     @OA\Property(property="diagnosis", type="string", example="Inattentive Type"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-20"),
     *                     @OA\Property(property="notes", type="string", example="Therapy schedule updated.")
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
