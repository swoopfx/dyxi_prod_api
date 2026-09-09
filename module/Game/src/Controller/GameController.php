<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Game\Service\GameService;
use Game\Entity\Game;

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

    public function __construct(GameService $gameService, ApiAuthenticateService $apiAuthService)
    {
        $this->gameService = $gameService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Create / Register a Game.
     *
     * @OA\Post(
     *     path="/api/game/game/create",
     *     tags={"Games"},
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
     *     tags={"Games"},
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
     *     tags={"Games"},
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
     *     tags={"Games"},
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
     *     tags={"Games"},
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
