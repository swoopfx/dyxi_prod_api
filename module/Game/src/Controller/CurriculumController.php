<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Game\Service\CurriculumService;
use Game\Entity\Curriculum;

class CurriculumController extends AbstractActionController
{
    /**
     * @var CurriculumService
     */
    private $curriculumService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    public function __construct(CurriculumService $curriculumService, ApiAuthenticateService $apiAuthService)
    {
        $this->curriculumService = $curriculumService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Create / Register a Curriculum.
     *
     * @OA\Post(
     *     path="/api/game/curriculum/create",
     *     tags={"Games"},
     *     summary="Create a new Curriculum",
     *     description="Creates a new Curriculum entity with selected games tracking (is_played, reason, custom_variables) and links to assigned ward.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to create a Curriculum.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"name"},
     *                     description="Curriculum Creation Schema",
     *                     @OA\Property(property="name", type="string", example="Primary Dyslexia Curriculum", description="[REQUIRED] Curriculum title."),
     *                     @OA\Property(property="description", type="string", example="Tailored curriculum for young readers.", description="[OPTIONAL] Detailed description."),
     *                     @OA\Property(property="min_age", type="integer", example=6, description="[OPTIONAL] Minimum recommended age."),
     *                     @OA\Property(property="max_age", type="integer", example=10, description="[OPTIONAL] Maximum recommended age."),
     *                     @OA\Property(property="ward_id", type="integer", example=2, description="[OPTIONAL] Specific Ward ID to bind this curriculum to."),
     *                     @OA\Property(
     *                         property="selected_games",
     *                         type="array",
     *                         description="[OPTIONAL] Array of selected games with tracking data.",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="game_id", type="integer", example=1),
     *                             @OA\Property(property="is_played", type="boolean", example=false),
     *                             @OA\Property(property="reason", type="string", example="Focus on visual perception."),
     *                             @OA\Property(property="custom_variables", type="object", example={"difficulty": "medium", "speed": 1.5})
     *                         )
     *                     ),
     *                     @OA\Property(property="game_ids", type="array", @OA\Items(type="integer", example=1), description="[OPTIONAL] Alternative simple list of Game IDs to associate.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Curriculum created successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="cur-uuid-12345"),
     *                         @OA\Property(property="name", type="string", example="Primary Dyslexia Curriculum"),
     *                         @OA\Property(property="description", type="string", example="Tailored curriculum for young readers."),
     *                         @OA\Property(property="min_age", type="integer", example=6),
     *                         @OA\Property(property="max_age", type="integer", example=10),
     *                         @OA\Property(
     *                             property="selected_games",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="game_id", type="integer", example=1),
     *                                 @OA\Property(property="is_played", type="boolean", example=false),
     *                                 @OA\Property(property="reason", type="string", example="Focus on visual perception."),
     *                                 @OA\Property(property="custom_variables", type="object")
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="ward",
     *                             type="object",
     *                             nullable=true,
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="uuid", type="string", example="ward-uuid-999"),
     *                             @OA\Property(property="fullname", type="string", example="John Doe"),
     *                             @OA\Property(property="unique_identifier", type="string", example="WD-1002")
     *                         ),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 14:00:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 14:00:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully registered Curriculum.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function createAction()
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

            $curriculum = $this->curriculumService->createCurriculum($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($curriculum),
                "description" => "Successfully registered Curriculum."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CurriculumRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias for createAction (POST /api/game/curriculum/register).
     */
    public function registerAction()
    {
        return $this->createAction();
    }

    /**
     * Recreate Curriculum for a Ward.
     *
     * @OA\Post(
     *     path="/api/game/curriculum/recreate",
     *     tags={"Games"},
     *     summary="Recreate Curriculum for Ward",
     *     description="Re-generates or re-assigns a curriculum tailored to a specific ward.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to recreate a curriculum for a ward.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"ward_id"},
     *                     description="Curriculum Recreation Schema",
     *                     @OA\Property(property="ward_id", type="integer", example=2, description="[REQUIRED] Ward ID for whom to recreate curriculum."),
     *                     @OA\Property(
     *                         property="selected_games",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="game_id", type="integer", example=1),
     *                             @OA\Property(property="reason", type="string", example="Reading exercise."),
     *                             @OA\Property(property="custom_variables", type="object")
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Curriculum recreated successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="data", type="object"),
     *                     @OA\Property(property="description", type="string", example="Successfully recreated Curriculum for Ward.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function recreateAction()
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

            $curriculum = $this->curriculumService->recreateCurriculumForWard($postData);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($curriculum),
                "description" => "Successfully recreated Curriculum for Ward."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CurriculumRecreationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * List all Curriculums.
     *
     * @OA\Get(
     *     path="/api/game/curriculum/list",
     *     tags={"Games"},
     *     summary="List all Curriculums",
     *     description="Retrieve list of all registered Curriculums including selected games tracking.",
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
     *                             @OA\Property(property="uuid", type="string", example="cur-uuid-12345"),
     *                             @OA\Property(property="name", type="string", example="Primary Dyslexia Curriculum"),
     *                             @OA\Property(
     *                                 property="selected_games",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="game_id", type="integer", example=1),
     *                                     @OA\Property(property="is_played", type="boolean", example=false),
     *                                     @OA\Property(property="reason", type="string", example="Focus on reading."),
     *                                     @OA\Property(property="custom_variables", type="object")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function listAction()
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

            $curriculums = $this->curriculumService->listCurriculums();
            $data = [];
            foreach ($curriculums as $c) {
                $data[] = $this->mapEntityToArray($c);
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
                "error" => "CurriculumListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * View Curriculum details.
     *
     * @OA\Get(
     *     path="/api/game/curriculum/info/{id}",
     *     tags={"Games"},
     *     summary="View Curriculum details",
     *     description="Retrieve detailed info for a single Curriculum by ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID, Curriculum UUID, or Ward UUID.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="data", type="object")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request - Curriculum not found"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function infoAction()
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
                $id = $this->params()->fromQuery('id') ?? $this->params()->fromQuery('uuid');
            }

            if (empty($id)) {
                throw new \Exception("Curriculum identifier (id or uuid) is required.");
            }

            $curriculum = $this->curriculumService->getCurriculumInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($curriculum)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CurriculumInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update a Curriculum.
     *
     * @OA\Put(
     *     path="/api/game/curriculum/update/{id}",
     *     tags={"Games"},
     *     summary="Update a Curriculum",
     *     description="Update existing Curriculum attributes and selected games list by ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the Curriculum.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update a Curriculum.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="name", type="string", example="Updated Curriculum Title"),
     *                     @OA\Property(property="description", type="string", example="Updated description text."),
     *                     @OA\Property(property="min_age", type="integer", example=7),
     *                     @OA\Property(property="max_age", type="integer", example=12),
     *                     @OA\Property(
     *                         property="selected_games",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="game_id", type="integer", example=1),
     *                             @OA\Property(property="is_played", type="boolean", example=true),
     *                             @OA\Property(property="reason", type="string", example="Updated reason."),
     *                             @OA\Property(property="custom_variables", type="object")
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="Curriculum updated successfully"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
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
                throw new \Exception("Curriculum identifier (id) is required in the path.");
            }

            $json = $request->getContent();
            $putData = (array) json_decode($json, true);

            $curriculum = $this->curriculumService->updateCurriculum($id, $putData);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($curriculum),
                "description" => "Successfully updated Curriculum."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CurriculumUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update Played Status / Custom Variables for a Game in Curriculum.
     *
     * @OA\Post(
     *     path="/api/game/curriculum/update-game-status",
     *     tags={"Games"},
     *     summary="Update Game Played Status & Custom Variables in Curriculum",
     *     description="Updates the is_played indicator, reason, and custom variables for a specific game within a curriculum (updated in DB & Redis).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update game status in curriculum.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"curriculum_id", "game_id"},
     *                     @OA\Property(property="curriculum_id", type="string", example="1", description="[REQUIRED] Curriculum ID, Curriculum UUID, or Ward UUID."),
     *                     @OA\Property(property="game_id", type="integer", example=1, description="[REQUIRED] Target Game ID or UUID."),
     *                     @OA\Property(property="is_played", type="boolean", example=true, description="[OPTIONAL] Played status indicator."),
     *                     @OA\Property(property="reason", type="string", example="Completed initial session.", description="[OPTIONAL] Reason string."),
     *                     @OA\Property(property="custom_variables", type="object", description="[OPTIONAL] Custom variables passed/returned for actual game execution.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Game status updated successfully in Curriculum",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="data", type="object"),
     *                     @OA\Property(property="description", type="string", example="Successfully updated Game played status in Curriculum.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function updateGameStatusAction()
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

            $curriculumId = $postData['curriculum_id'] ?? $postData['curriculum_uuid'] ?? $postData['ward_uuid'] ?? null;
            $gameId = $postData['game_id'] ?? null;

            if (empty($curriculumId) || empty($gameId)) {
                throw new \Exception("Both 'curriculum_id' and 'game_id' are required.");
            }

            $isPlayed = isset($postData['is_played']) ? (bool) $postData['is_played'] : true;
            $customVariables = $postData['custom_variables'] ?? $postData['custom_variable'] ?? null;
            $reason = isset($postData['reason']) ? (string) $postData['reason'] : null;

            $curriculum = $this->curriculumService->updateGamePlayedStatus($curriculumId, $gameId, $isPlayed, $customVariables, $reason);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($curriculum),
                "description" => "Successfully updated Game played status in Curriculum."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "UpdateGameStatusError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete a Curriculum.
     *
     * @OA\Delete(
     *     path="/api/game/curriculum/delete/{id}",
     *     tags={"Games"},
     *     summary="Delete a Curriculum",
     *     description="Deletes a Curriculum record by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the Curriculum to delete.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response="200", description="Curriculum deleted successfully"),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
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
                throw new \Exception("Curriculum identifier (id) is required in the path.");
            }

            $this->curriculumService->deleteCurriculum($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted Curriculum."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CurriculumDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    private function mapEntityToArray(Curriculum $curriculum): array
    {
        return [
            "id" => $curriculum->getId(),
            "uuid" => $curriculum->getUuid(),
            "name" => $curriculum->getName(),
            "description" => $curriculum->getDescription(),
            "min_age" => $curriculum->getMinAge(),
            "max_age" => $curriculum->getMaxAge(),
            "selected_games" => $curriculum->getSelectedGames() ?? [],
            "ward" => $curriculum->getWard() ? [
                "id" => $curriculum->getWard()->getId(),
                "uuid" => $curriculum->getWard()->getUuid(),
                "fullname" => $curriculum->getWard()->getFullname(),
                "unique_identifier" => $curriculum->getWard()->getUniqueIdentifier(),
            ] : null,
            "created_on" => $curriculum->getCreatedOn() ? $curriculum->getCreatedOn()->format('Y-m-d H:i:s') : null,
            "updated_on" => $curriculum->getUpdatedOn() ? $curriculum->getUpdatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}

