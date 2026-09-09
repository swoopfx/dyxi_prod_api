<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Game\Service\GameService;
use Game\Entity\GameType;

class GameTypeController extends AbstractActionController
{
    /**
     * @var GameService
     */
    private $gameService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    public function __construct(GameService $gameService, ApiAuthenticateService $apiAuthService)
    {
        $this->gameService = $gameService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Create / Register a GameType.
     *
     * @OA\Post(
     *     path="/api/game/game-type/create",
     *     tags={"Games"},
     *     summary="Create a new GameType",
     *     description="Creates a new GameType entry.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to create a GameType. Required field: 'name'.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"name"},
     *                     description="GameType Creation Schema",
     *                     @OA\Property(property="name", type="string", example="Puzzle", description="[REQUIRED] Unique name of the GameType."),
     *                     @OA\Property(property="description", type="string", example="Logic and puzzle solving games.", description="[OPTIONAL] Description of the GameType."),
     *                     @OA\Property(property="uuid", type="string", example="gt-uuid-12345", description="[OPTIONAL] UUID v4 string.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="GameType created successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="gt-uuid-12345"),
     *                         @OA\Property(property="name", type="string", example="Puzzle"),
     *                         @OA\Property(property="description", type="string", example="Logic and puzzle solving games."),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 14:00:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 14:00:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully registered GameType.")
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

            $gameType = $this->gameService->createGameType($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($gameType),
                "description" => "Successfully registered GameType."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameTypeRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias for createAction (POST /api/game/game-type/register).
     */
    public function registerAction()
    {
        return $this->createAction();
    }

    /**
     * Lists GameTypes.
     *
     * @OA\Get(
     *     path="/api/game/game-type/list",
     *     tags={"Games"},
     *     summary="List all GameTypes",
     *     description="Retrieve list of all registered GameTypes.",
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
     *                             @OA\Property(property="uuid", type="string", example="gt-uuid-12345"),
     *                             @OA\Property(property="name", type="string", example="Puzzle"),
     *                             @OA\Property(property="description", type="string", example="Logic and puzzle solving games.")
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

            $gameTypes = $this->gameService->listGameTypes();
            $data = [];
            foreach ($gameTypes as $gt) {
                $data[] = $this->mapEntityToArray($gt);
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
                "error" => "GameTypeListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Get GameType info.
     *
     * @OA\Get(
     *     path="/api/game/game-type/info/{id}",
     *     tags={"Games"},
     *     summary="View GameType details",
     *     description="Retrieve details of a single GameType by integer ID, UUID, or name.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID, UUID, or name string of the GameType.",
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
     *                         @OA\Property(property="uuid", type="string", example="gt-uuid-12345"),
     *                         @OA\Property(property="name", type="string", example="Puzzle"),
     *                         @OA\Property(property="description", type="string", example="Logic and puzzle solving games.")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request - GameType not found"),
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
                throw new \Exception("GameType identifier (id or uuid) is required.");
            }

            $gameType = $this->gameService->getGameTypeInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($gameType)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameTypeInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update a GameType.
     *
     * @OA\Put(
     *     path="/api/game/game-type/update/{id}",
     *     tags={"Games"},
     *     summary="Update a GameType",
     *     description="Update existing GameType details by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the GameType.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update a GameType.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="name", type="string", example="Advanced Puzzle"),
     *                     @OA\Property(property="description", type="string", example="Updated description.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="GameType updated successfully"),
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
                throw new \Exception("GameType identifier (id) is required in the path.");
            }

            $json = $request->getContent();
            $putData = (array) json_decode($json, true);

            $gameType = $this->gameService->updateGameType($id, $putData);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($gameType),
                "description" => "Successfully updated GameType."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameTypeUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete a GameType.
     *
     * @OA\Delete(
     *     path="/api/game/game-type/delete/{id}",
     *     tags={"Games"},
     *     summary="Delete a GameType",
     *     description="Deletes a GameType record by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the GameType to delete.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response="200", description="GameType deleted successfully"),
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
                throw new \Exception("GameType identifier (id) is required in the path.");
            }

            $this->gameService->deleteGameType($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted GameType."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "GameTypeDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    private function mapEntityToArray(GameType $gameType): array
    {
        return [
            "id" => $gameType->getId(),
            "uuid" => $gameType->getUuid(),
            "name" => $gameType->getName(),
            "description" => $gameType->getDescription(),
            "created_on" => $gameType->getCreatedOn() ? $gameType->getCreatedOn()->format('Y-m-d H:i:s') : null,
            "updated_on" => $gameType->getUpdatedOn() ? $gameType->getUpdatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}
