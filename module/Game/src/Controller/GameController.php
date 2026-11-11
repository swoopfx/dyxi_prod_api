<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Game\Service\GameService;
use Game\Entity\Game;
use General\Service\BigQueryService;
use Ramsey\Uuid\Uuid;

/**
 * @OA\Tag(
 *     name="Games",
 *     description="API endpoints for managing Games, Game Types, Curriculums, and Collections."
 * )
 */
class GameController extends AbstractActionController
{
    /**
     * @var GameService
     */
    private $gameService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * @var BigQueryService|null
     */
    private ?BigQueryService $bigQueryService;

    public function __construct(
        GameService $gameService,
        ApiAuthenticateService $apiAuthService,
        ?BigQueryService $bigQueryService = null
    ) {
        $this->gameService = $gameService;
        $this->apiAuthService = $apiAuthService;
        $this->bigQueryService = $bigQueryService;
    }

    /**
     * Create / Register a Game.
     *
     * @OA\Post(
     *     path="/api/game/game/create",
     *     tags={"User Interface - Learning & Games", "Admin Interface - Catalog & Toddler Management", "Games"},
     *     summary="Create a new Game",
     *     description="Creates a new Game entry with game type, optional curriculum, summary, description, and target tags.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to create a Game. Required fields: 'title' and 'game_type_id'.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"title", "game_type_id"},
     *                     description="Game Creation Schema",
     *                     @OA\Property(property="title", type="string", example="Math Quest Adventure", description="[REQUIRED] Title of the game."),
     *                     @OA\Property(property="game_type_id", type="string", example="1", description="[REQUIRED] GameType ID or UUID."),
     *                     @OA\Property(property="summary", type="string", example="An interactive math adventure game for kids.", description="[OPTIONAL] Short summary overview of the game."),
     *                     @OA\Property(property="description", type="string", example="Comprehensive math quest game designed to improve numeracy and logical thinking...", description="[OPTIONAL] Detailed description of the game."),
     *                     @OA\Property(property="curriculum_id", type="string", example="1", description="[OPTIONAL] Curriculum ID or UUID."),
     *                     @OA\Property(property="target_tags", type="array", @OA\Items(type="string", example="ADHD"), description="[OPTIONAL] List of target tag names, IDs, or UUIDs (e.g. ['ADHD', 'Dyslexia'])."),
     *                     @OA\Property(property="unique_identifier", type="string", example="GAME-M1001", description="[OPTIONAL] Custom unique identifier string."),
     *                     @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045", description="[OPTIONAL] UUID v4 string.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Game created successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                         @OA\Property(property="unique_identifier", type="string", example="GAME-M1001"),
     *                         @OA\Property(property="title", type="string", example="Math Quest Adventure"),
     *                         @OA\Property(property="summary", type="string", example="An interactive math adventure game for kids."),
     *                         @OA\Property(property="description", type="string", example="Comprehensive math quest game..."),
     *                         @OA\Property(
     *                             property="game_type",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Puzzle"),
     *                             @OA\Property(property="uuid", type="string", example="gt-uuid-123")
     *                         ),
     *                         @OA\Property(
     *                             property="curriculum",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Elementary Math"),
     *                             @OA\Property(property="uuid", type="string", example="curr-uuid-456")
     *                         ),
     *                         @OA\Property(
     *                             property="target_tags",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="uuid", type="string", example="tag-uuid-123"),
     *                                 @OA\Property(property="name", type="string", example="ADHD"),
     *                                 @OA\Property(property="description", type="string", example="Attention Deficit Hyperactivity Disorder")
     *                             )
     *                         ),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 14:00:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 14:00:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully registered Game.")
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
            if (empty($postData)) {
                $postData = $request->getPost()->toArray();
            }

            $game = $this->gameService->createGame($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($game),
                "description" => "Successfully registered Game."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias for createAction (POST /api/game/game/register).
     */
    public function registerAction()
    {
        return $this->createAction();
    }

    /**
     * Lists Games.
     *
     * @OA\Get(
     *     path="/api/game/game/list",
     *     tags={"User Interface - Learning & Games", "Admin Interface - Catalog & Toddler Management", "Games"},
     *     summary="List all Games",
     *     description="Retrieve list of all registered games.",
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
     *                             @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                             @OA\Property(property="unique_identifier", type="string", example="GAME-M1001"),
     *                             @OA\Property(property="title", type="string", example="Math Quest Adventure"),
     *                             @OA\Property(property="summary", type="string", example="An interactive math adventure game for kids."),
     *                             @OA\Property(property="description", type="string", example="Comprehensive math quest game..."),
     *                             @OA\Property(property="created_on", type="string", example="2026-09-09 14:00:00"),
     *                             @OA\Property(property="updated_on", type="string", example="2026-09-09 14:00:00")
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

            $games = $this->gameService->listGames();
            $data = [];
            foreach ($games as $g) {
                $data[] = $this->mapEntityToArray($g);
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
                "error" => "GameListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Get Game info.
     *
     * @OA\Get(
     *     path="/api/game/game/info/{id}",
     *     tags={"User Interface - Learning & Games", "Admin Interface - Catalog & Toddler Management", "Games"},
     *     summary="View Game details",
     *     description="Retrieve details of a single Game by integer ID, UUID, or unique_identifier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID, UUID, or unique_identifier string.",
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
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="7b7f1ad9-d9d5-451e-8ef9-eb9915159045"),
     *                         @OA\Property(property="unique_identifier", type="string", example="GAME-M1001"),
     *                         @OA\Property(property="title", type="string", example="Math Quest Adventure"),
     *                         @OA\Property(property="summary", type="string", example="An interactive math adventure game for kids."),
     *                         @OA\Property(property="description", type="string", example="Comprehensive math quest game...")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request - Game not found"),
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
                $id = $this->params()->fromQuery('id') 
                    ?? $this->params()->fromQuery('uuid') 
                    ?? $this->params()->fromQuery('unique_identifier');
            }

            if (empty($id)) {
                throw new \Exception("Game identifier (id, uuid, or unique_identifier) is required.");
            }

            $game = $this->gameService->getGameInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($game)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update a Game.
     *
     * @OA\Put(
     *     path="/api/game/game/update/{id}",
     *     tags={"User Interface - Learning & Games", "Admin Interface - Catalog & Toddler Management", "Games"},
     *     summary="Update a Game",
     *     description="Update existing Game details by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the game.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update a Game.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="title", type="string", example="Updated Math Quest"),
     *                     @OA\Property(property="summary", type="string", example="Updated summary text."),
     *                     @OA\Property(property="description", type="string", example="Updated description text."),
     *                     @OA\Property(property="game_type_id", type="string", example="2"),
     *                     @OA\Property(property="curriculum_id", type="string", example="1"),
     *                     @OA\Property(property="target_tags", type="array", @OA\Items(type="string", example="ADHD"))
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="Game updated successfully"),
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
                throw new \Exception("Game identifier (id) is required in the path.");
            }

            $json = $request->getContent();
            $putData = (array) json_decode($json, true);

            $game = $this->gameService->updateGame($id, $putData);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($game),
                "description" => "Successfully updated Game."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete a Game.
     *
     * @OA\Delete(
     *     path="/api/game/game/delete/{id}",
     *     tags={"User Interface - Learning & Games", "Admin Interface - Catalog & Toddler Management", "Games"},
     *     summary="Delete a Game",
     *     description="Deletes a Game record by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the game to delete.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response="200", description="Game deleted successfully"),
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
                throw new \Exception("Game identifier (id) is required in the path.");
            }

            $this->gameService->deleteGame($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted Game."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Post Generic Telemetry and Assessment Data into Google BigQuery.
     *
     * @OA\Post(
     *     path="/api/game/game/post-bigquery",
     *     tags={"Games", "Analytics & BigQuery"},
     *     summary="Stream Generic Data into Google BigQuery",
     *     description="Dynamic ingestion endpoint that receives raw JSON payload in 'rawData' (object, array of objects, or raw JSON string) that varies depending on the target 'tableName'. The JSON payload is parsed/extracted and only the relevant fields matching the target BigQuery table's schema are inserted into the database. Every payload MUST always be accompanied by non-empty 'age' (numeric and non-negative) and 'user_id' (string), which are validated as required fields (either embedded in rawData records or supplied at root).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Generic BigQuery Ingestion Payload containing tableName, rawData (varying raw JSON payload), optional dataset, and required age and user_id.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"tableName", "rawData"},
     *                     description="Generic BigQuery Ingestion Payload Schema",
     *                     @OA\Property(
     *                         property="tableName",
     *                         type="string",
     *                         example="recog_shape_recog",
     *                         description="[REQUIRED] Target BigQuery table name."
     *                     ),
     *                     @OA\Property(
     *                         property="rawData",
     *                         description="[REQUIRED] Ingestion data payload: raw JSON object, array of objects, or raw JSON string whose dynamic fields change depending on the defined 'tableName'. The payload is extracted and only relevant schema fields are inserted into BigQuery. Must be accompanied by required 'age' and 'user_id' (which can be provided inside rawData or at the root request level).",
     *                         oneOf={
     *                             @OA\Schema(type="object"),
     *                             @OA\Schema(type="array", @OA\Items(type="object")),
     *                             @OA\Schema(type="string")
     *                         }
     *                     ),
     *                     @OA\Property(
     *                         property="age",
     *                         type="number",
     *                         example=5,
     *                         description="[REQUIRED] Age of the user/ward. Must be numeric and non-negative. Required in all ingestion payloads (can accompany at root or inside rawData records)."
     *                     ),
     *                     @OA\Property(
     *                         property="user_id",
     *                         type="string",
     *                         example="usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10",
     *                         description="[REQUIRED] Target user identifier. Cannot be empty. Required in all ingestion payloads (can accompany at root or inside rawData records)."
     *                     ),
     *                     @OA\Property(
     *                         property="dataset",
     *                         type="string",
     *                         example="dyxi_api_pre_prod",
     *                         description="[OPTIONAL] Target BigQuery dataset ID. Defaults to configured environment dataset ('dyxi_api_pre_prod') if omitted."
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Data successfully ingested into BigQuery",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="table", type="string", example="recog_shape_recog"),
     *                         @OA\Property(property="dataset", type="string", example="dyxi_api_pre_prod"),
     *                         @OA\Property(property="inserted_count", type="integer", example=1),
     *                         @OA\Property(property="method", type="string", example="streaming_insert"),
     *                         @OA\Property(property="session_id", type="string", example="a9f24e10-9b3c-4d5e-8f12-0c3b5d7e9f20"),
     *                         @OA\Property(property="records", type="object")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully posted 1 record(s) into BigQuery table 'recog_shape_recog' (dataset: 'dyxi_api_pre_prod').")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request - Validation failure (missing tableName, missing/invalid age, missing/empty user_id, malformed JSON, or missing rawData)",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ValidationFailed"),
     *                     @OA\Property(property="description", type="string", example="The 'tableName' parameter is required and must be a string.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized - Missing or invalid bearer token / user identity",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="Unauthorized"),
     *                     @OA\Property(property="description", type="string", example="User identity not found in request context.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found - BigQuery table does not exist in target dataset",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="TableNotFound"),
     *                     @OA\Property(property="description", type="string", example="BigQuery table 'unknown_table' does not exist in dataset 'dyxi_api_pre_prod'.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Method Not Allowed - Only POST is permitted"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Server Error - BigQuery service unavailable or ingestion failure"
     *     )
     * )
     */
    public function postBigqueryAction(): JsonModel
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        // 1. Method filter: Require POST
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
            // 2. Authentication filter: Identity check
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success"     => false,
                    "error"       => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            // 3. Request Body parsing & JSON validation
            $rawJson = (string) $request->getContent();
            $postData = [];
            if (!empty($rawJson)) {
                $decoded = json_decode($rawJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $response->setStatusCode(400);
                    $jsonModel->setVariables([
                        "success"     => false,
                        "error"       => "InvalidJsonPayload",
                        "description" => "Malformed JSON body: " . json_last_error_msg()
                    ]);
                    return $jsonModel;
                }
                if (is_array($decoded)) {
                    $postData = $decoded;
                }
            }
            if (empty($postData)) {
                $postData = $request->getPost()->toArray();
            }

            if (empty($postData)) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success"     => false,
                    "error"       => "EmptyPayload",
                    "description" => "Request payload cannot be empty."
                ]);
                return $jsonModel;
            }

            // 4. Extract table name: accepts tableName, table_name, table
            $rawTable = $postData['tableName'] ?? $postData['table_name'] ?? $postData['table'] ?? null;
            if (empty($rawTable) || !is_string($rawTable)) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success"     => false,
                    "error"       => "ValidationFailed",
                    "description" => "The 'tableName' parameter is required and must be a string."
                ]);
                return $jsonModel;
            }

            $tableName = trim($rawTable);

            // 5. Extract optional dataset ID: accepts dataset, dataset_id, datasetId
            $datasetId = $postData['dataset'] ?? $postData['dataset_id'] ?? $postData['datasetId'] ?? null;

            // 6. Extract data payload: accepts rawData, data, rows, or root fields
            $rawData = $postData['rawData'] ?? $postData['data'] ?? $postData['rows'] ?? null;
            if ($rawData === null) {
                $reservedKeys = ['tableName', 'table_name', 'table', 'dataset', 'dataset_id', 'datasetId', 'rawData', 'data', 'rows', 'age', 'user_id', 'user_uuid'];
                $dataFields = array_diff_key($postData, array_flip($reservedKeys));
                if (!empty($dataFields)) {
                    $rawData = $dataFields;
                }
            }

            // If rawData is sent as a JSON string, extract/decode it
            if (is_string($rawData)) {
                $trimmed = trim($rawData);
                $decodedRaw = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedRaw) || is_object($decodedRaw))) {
                    $rawData = (array) $decodedRaw;
                } else {
                    $response->setStatusCode(400);
                    $jsonModel->setVariables([
                        "success"     => false,
                        "error"       => "InvalidRawDataJson",
                        "description" => "The 'rawData' JSON string is invalid or malformed: " . json_last_error_msg()
                    ]);
                    return $jsonModel;
                }
            }

            // If rawData is an array containing JSON string items, decode each item
            if (is_array($rawData)) {
                foreach ($rawData as $k => $item) {
                    if (is_string($item)) {
                        $decodedItem = json_decode(trim($item), true);
                        if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedItem) || is_object($decodedItem))) {
                            $rawData[$k] = (array) $decodedItem;
                        }
                    }
                }
            }

            if (empty($rawData) || (!is_array($rawData) && !is_object($rawData))) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success"     => false,
                    "error"       => "MissingDataPayload",
                    "description" => "The 'rawData' parameter is required and must be an object, array of objects, or valid JSON string."
                ]);
                return $jsonModel;
            }

            // 7. Ensure age and user_id accompany the payload (propagate root-level values if present)
            $rootAge = $postData['age'] ?? null;
            $rootUserId = $postData['user_id'] ?? $postData['user_uuid'] ?? null;

            $isBatch = isset($rawData[0]) && is_array($rawData[0]);
            if (!$isBatch) {
                if ($rootAge !== null && (!isset($rawData['age']) || $rawData['age'] === '')) {
                    $rawData['age'] = $rootAge;
                }
                if ($rootUserId !== null && (!isset($rawData['user_id']) || $rawData['user_id'] === '') && (!isset($rawData['user_uuid']) || $rawData['user_uuid'] === '')) {
                    $rawData['user_id'] = $rootUserId;
                }
            } else {
                foreach ($rawData as &$record) {
                    if (is_array($record)) {
                        if ($rootAge !== null && (!isset($record['age']) || $record['age'] === '')) {
                            $record['age'] = $rootAge;
                        }
                        if ($rootUserId !== null && (!isset($record['user_id']) || $record['user_id'] === '') && (!isset($record['user_uuid']) || $record['user_uuid'] === '')) {
                            $record['user_id'] = $rootUserId;
                        }
                    }
                }
                unset($record);
            }

            // 8. Validate that age and user_id are present and valid for every record
            $recordsToValidate = $isBatch ? $rawData : [$rawData];
            foreach ($recordsToValidate as $idx => $record) {
                if (!is_array($record)) {
                    throw new \InvalidArgumentException(sprintf("Record at index %d must be an object/associative array.", $idx));
                }

                if (!array_key_exists('age', $record) || $record['age'] === null || $record['age'] === '' || (is_string($record['age']) && trim((string) $record['age']) === '')) {
                    throw new \InvalidArgumentException(sprintf(
                        "Validation failed: Record at index %d is missing required field 'age' or it is empty.",
                        $idx
                    ));
                }

                if (!is_numeric($record['age']) || (float) $record['age'] < 0) {
                    throw new \InvalidArgumentException(sprintf(
                        "Validation failed: Record at index %d field 'age' must be a valid positive number.",
                        $idx
                    ));
                }

                $userId = $record['user_id'] ?? $record['user_uuid'] ?? null;
                if ($userId === null || trim((string) $userId) === '') {
                    throw new \InvalidArgumentException(sprintf(
                        "Validation failed: Record at index %d is missing required field 'user_id' or it is empty.",
                        $idx
                    ));
                }
            }

            // Ensure GameService has BigQueryService available
            if ($this->bigQueryService && method_exists($this->gameService, 'getBigQueryService') && !$this->gameService->getBigQueryService()) {
                $this->gameService->setBigQueryService($this->bigQueryService);
            }

            // 9. Generic delegation to GameService (actual extraction and insertion handled according to table schema)
            $result = $this->gameService->postBigQueryData($tableName, $rawData, $datasetId, $identity);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success"     => true,
                "data"        => $result,
                "description" => sprintf(
                    "Successfully posted %d record(s) into BigQuery table '%s' (dataset: '%s').",
                    $result['inserted_count'] ?? 1,
                    $result['table'] ?? $tableName,
                    $result['dataset'] ?? ($datasetId ?: 'default')
                )
            ]);

        } catch (\InvalidArgumentException $e) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "ValidationFailed",
                "description" => $e->getMessage()
            ]);
        } catch (\Throwable $th) {
            $statusCode = str_contains(strtolower($th->getMessage()), 'does not exist') ? 404 : 400;
            $response->setStatusCode($statusCode);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => $statusCode === 404 ? "TableNotFound" : "BigQueryIngestionError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias endpoint for BigQuery ingestion (/api/game/game/bigquery).
     */
    public function bigqueryAction(): JsonModel
    {
        return $this->postBigqueryAction();
    }

    private function mapEntityToArray(Game $game): array
    {
        $targetTagsData = [];
        if (method_exists($game, 'getTargetTags') && $game->getTargetTags()) {
            foreach ($game->getTargetTags() as $tag) {
                $targetTagsData[] = [
                    "id" => $tag->getId(),
                    "uuid" => $tag->getUuid(),
                    "name" => $tag->getName(),
                    "description" => $tag->getDescription()
                ];
            }
        }

        return [
            "id" => $game->getId(),
            "uuid" => $game->getUuid(),
            "unique_identifier" => $game->getUniqueIdentifier(),
            "title" => $game->getTitle(),
            "summary" => $game->getSummary(),
            "description" => $game->getDescription(),
            "game_type" => $game->getGameType() ? [
                "id" => $game->getGameType()->getId(),
                "name" => $game->getGameType()->getName(),
                "uuid" => $game->getGameType()->getUuid()
            ] : null,
            "curriculum" => $game->getCurriculum() ? [
                "id" => $game->getCurriculum()->getId(),
                "name" => $game->getCurriculum()->getName(),
                "uuid" => $game->getCurriculum()->getUuid()
            ] : null,
            "target_tags" => $targetTagsData,
            "created_on" => $game->getCreatedOn() ? $game->getCreatedOn()->format('Y-m-d H:i:s') : null,
            "updated_on" => $game->getUpdatedOn() ? $game->getUpdatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}
