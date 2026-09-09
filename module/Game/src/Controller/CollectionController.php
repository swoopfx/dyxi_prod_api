<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Game\Service\GameService;
use Game\Entity\GamesCollection;

class CollectionController extends AbstractActionController
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
     * Create / Register a GamesCollection.
     *
     * @OA\Post(
     *     path="/api/game/collection/create",
     *     tags={"Games"},
     *     summary="Create a new GamesCollection",
     *     description="Creates a new GamesCollection and optionally links games to it.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to create a GamesCollection.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"name"},
     *                     description="GamesCollection Creation Schema",
     *                     @OA\Property(property="name", type="string", example="Starter Math Collection", description="[REQUIRED] Collection title."),
     *                     @OA\Property(property="description", type="string", example="Collection of basic arithmetic games.", description="[OPTIONAL] Collection description."),
     *                     @OA\Property(property="game_ids", type="array", @OA\Items(type="integer", example=1), description="[OPTIONAL] Array of Game IDs to include.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="GamesCollection created successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="col-uuid-12345"),
     *                         @OA\Property(property="name", type="string", example="Starter Math Collection"),
     *                         @OA\Property(property="description", type="string", example="Collection of basic arithmetic games."),
     *                         @OA\Property(
     *                             property="games",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="uuid", type="string", example="game-uuid-123"),
     *                                 @OA\Property(property="title", type="string", example="Math Speed Quest"),
     *                                 @OA\Property(property="unique_identifier", type="string", example="MATH-001")
     *                             )
     *                         ),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 14:00:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 14:00:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully registered GamesCollection.")
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

            $collection = $this->gameService->createCollection($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($collection),
                "description" => "Successfully registered GamesCollection."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CollectionRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias for createAction (POST /api/game/collection/register).
     */
    public function registerAction()
    {
        return $this->createAction();
    }

    /**
     * List GamesCollections.
     *
     * @OA\Get(
     *     path="/api/game/collection/list",
     *     tags={"Games"},
     *     summary="List all GamesCollections",
     *     description="Retrieve list of all registered GamesCollections.",
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
     *                             @OA\Property(property="uuid", type="string", example="col-uuid-12345"),
     *                             @OA\Property(property="name", type="string", example="Starter Math Collection")
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

            $collections = $this->gameService->listCollections();
            $data = [];
            foreach ($collections as $col) {
                $data[] = $this->mapEntityToArray($col);
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
                "error" => "CollectionListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * View GamesCollection info.
     *
     * @OA\Get(
     *     path="/api/game/collection/info/{id}",
     *     tags={"Games"},
     *     summary="View GamesCollection details",
     *     description="Retrieve details of a single GamesCollection by ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the GamesCollection.",
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
     *     @OA\Response(response="400", description="Bad Request - Collection not found"),
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
                throw new \Exception("GamesCollection identifier (id or uuid) is required.");
            }

            $collection = $this->gameService->getCollectionInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($collection)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CollectionInfoError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Update a GamesCollection.
     *
     * @OA\Put(
     *     path="/api/game/collection/update/{id}",
     *     tags={"Games"},
     *     summary="Update a GamesCollection",
     *     description="Update existing GamesCollection details by ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the GamesCollection.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to update a GamesCollection.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="name", type="string", example="Updated Collection Title"),
     *                     @OA\Property(property="description", type="string", example="Updated description."),
     *                     @OA\Property(property="game_ids", type="array", @OA\Items(type="integer", example=1))
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="200", description="GamesCollection updated successfully"),
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
                throw new \Exception("GamesCollection identifier (id) is required in the path.");
            }

            $json = $request->getContent();
            $putData = (array) json_decode($json, true);

            $collection = $this->gameService->updateCollection($id, $putData);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($collection),
                "description" => "Successfully updated GamesCollection."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CollectionUpdateError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Delete a GamesCollection.
     *
     * @OA\Delete(
     *     path="/api/game/collection/delete/{id}",
     *     tags={"Games"},
     *     summary="Delete a GamesCollection",
     *     description="Deletes a GamesCollection record by integer ID or UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Integer ID or UUID of the GamesCollection to delete.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response="200", description="GamesCollection deleted successfully"),
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
                throw new \Exception("GamesCollection identifier (id) is required in the path.");
            }

            $this->gameService->deleteCollection($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "description" => "Successfully deleted GamesCollection."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "CollectionDeleteError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    private function mapEntityToArray(GamesCollection $collection): array
    {
        $gamesData = [];
        foreach ($collection->getGames() as $game) {
            $gamesData[] = [
                "id" => $game->getId(),
                "uuid" => $game->getUuid(),
                "title" => $game->getTitle(),
                "unique_identifier" => $game->getUniqueIdentifier()
            ];
        }

        return [
            "id" => $collection->getId(),
            "uuid" => $collection->getUuid(),
            "name" => $collection->getName(),
            "description" => $collection->getDescription(),
            "games" => $gamesData,
            "created_on" => $collection->getCreatedOn()->format('Y-m-d H:i:s'),
            "updated_on" => $collection->getUpdatedOn()->format('Y-m-d H:i:s'),
        ];
    }
}

