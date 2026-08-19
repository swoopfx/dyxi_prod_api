<?php

namespace Dyslexia\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Dyslexia\Service\DyslexiaService;

class DyslexiaController extends AbstractActionController
{
    /**
     * @var DyslexiaService
     */
    private $dyslexiaService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * DyslexiaController constructor.
     *
     * @param DyslexiaService $dyslexiaService
     * @param ApiAuthenticateService $apiAuthService
     */
    public function __construct(DyslexiaService $dyslexiaService, ApiAuthenticateService $apiAuthService)
    {
        $this->dyslexiaService = $dyslexiaService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Registers a new Dyslexia assessment.
     *
     * @OA\Post(
     *     path="/api/dyslexia/register",
     *     tags={"Dyslexia"},
     *     description="Registers a new Dyslexia assessment for a ward.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"ward_id", "phonological_awareness_score", "rapid_naming_score", "word_reading_score", "subtype", "assessment_date"},
     *                     @OA\Property(property="ward_id", type="string", example="1", description="ID, UUID or unique identifier of the ward"),
     *                     @OA\Property(property="phonological_awareness_score", type="integer", example=85),
     *                     @OA\Property(property="rapid_naming_score", type="integer", example=90),
     *                     @OA\Property(property="word_reading_score", type="integer", example=80),
     *                     @OA\Property(property="subtype", type="string", example="Phonological"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-19", description="Date of assessment (YYYY-MM-DD)"),
     *                     @OA\Property(property="notes", type="string", example="Further training recommended."),
     *                     @OA\Property(property="unique_identifier", type="string", example="DYS-ABC-123"),
     *                     @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045")
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

            $assessment = $this->dyslexiaService->register($postData, $identity);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully registered Dyslexia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyslexiaRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Lists Dyslexia assessments.
     *
     * @OA\Get(
     *     path="/api/dyslexia/list-assessments",
     *     tags={"Dyslexia"},
     *     description="Retrieve a list of all Dyslexia assessments for the authenticated user's wards.",
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

            $assessments = $this->dyslexiaService->listAssessments($identity);
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
                "error" => "DyslexiaListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Get details of a Dyslexia assessment.
     *
     * @OA\Get(
     *     path="/api/dyslexia/assessment-info/{id}",
     *     tags={"Dyslexia"},
     *     description="Retrieve details of a specific Dyslexia assessment by ID, UUID, or unique identifier.",
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

            $assessment = $this->dyslexiaService->getAssessmentInfo($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyslexiaInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update an existing Dyslexia assessment.
     *
     * @OA\Put(
     *     path="/api/dyslexia/update/{id}",
     *     tags={"Dyslexia"},
     *     description="Update an existing Dyslexia assessment.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="phonological_awareness_score", type="integer", example=88),
     *                     @OA\Property(property="rapid_naming_score", type="integer", example=92),
     *                     @OA\Property(property="word_reading_score", type="integer", example=85),
     *                     @OA\Property(property="subtype", type="string", example="Surface"),
     *                     @OA\Property(property="assessment_date", type="string", example="2026-08-20"),
     *                     @OA\Property(property="notes", type="string", example="Improved scores.")
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

            $assessment = $this->dyslexiaService->update($id, $putData, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($assessment),
                "description" => "Successfully updated Dyslexia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyslexiaUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete an assessment.
     *
     * @OA\Delete(
     *     path="/api/dyslexia/delete/{id}",
     *     tags={"Dyslexia"},
     *     description="Delete an existing Dyslexia assessment.",
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

            $this->dyslexiaService->delete($id, $identity);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted Dyslexia assessment."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "DyslexiaDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Map Dyslexia Entity properties to an associative array.
     */
    private function mapEntityToArray($dyslexia)
    {
        return [
            "id" => $dyslexia->getId(),
            "uuid" => $dyslexia->getUuid(),
            "unique_identifier" => $dyslexia->getUniqueIdentifier(),
            "ward_id" => $dyslexia->getWard()->getId(),
            "ward_fullname" => $dyslexia->getWard()->getFullname(),
            "assessment_date" => $dyslexia->getAssessmentDate()->format('Y-m-d'),
            "phonological_awareness_score" => $dyslexia->getPhonologicalAwarenessScore(),
            "rapid_naming_score" => $dyslexia->getRapidNamingScore(),
            "word_reading_score" => $dyslexia->getWordReadingScore(),
            "total_score" => $dyslexia->getTotalScore(),
            "subtype" => $dyslexia->getSubtype(),
            "notes" => $dyslexia->getNotes()
        ];
    }
}
