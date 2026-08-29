<?php

namespace Game\Service;

use Doctrine\ORM\EntityManager;
use Game\Entity\GameType;
use Game\Entity\Curriculum;
use Game\Entity\Game;
use Game\Entity\GamesCollection;
use Ramsey\Uuid\Uuid;

class GameService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * GameService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // ==========================================
    // GAME TYPE CRUD
    // ==========================================

    public function createGameType(array $data): GameType
    {
        if (empty($data['name'])) {
            throw new \Exception("GameType name is required.");
        }

        $existing = $this->entityManager->getRepository(GameType::class)->findOneBy(['name' => $data['name']]);
        if ($existing) {
            throw new \Exception("A GameType with this name already exists.");
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $gameType = new GameType();
        $gameType->setUuid($uuid)
                 ->setName($data['name'])
                 ->setDescription($data['description'] ?? null);

        $this->entityManager->persist($gameType);
        $this->entityManager->flush();

        return $gameType;
    }

    public function listGameTypes(): array
    {
        return $this->entityManager->getRepository(GameType::class)->findAll();
    }

    public function getGameTypeInfo($idOrUuid): GameType
    {
        $repo = $this->entityManager->getRepository(GameType::class);
        $gameType = null;

        if (is_numeric($idOrUuid)) {
            $gameType = $repo->find((int) $idOrUuid);
        }
        if (!$gameType) {
            $gameType = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$gameType) {
            $gameType = $repo->findOneBy(['name' => $idOrUuid]);
        }

        if (!$gameType) {
            throw new \Exception("GameType not found.");
        }

        return $gameType;
    }

    public function updateGameType($idOrUuid, array $data): GameType
    {
        $gameType = $this->getGameTypeInfo($idOrUuid);

        if (!empty($data['name'])) {
            $existing = $this->entityManager->getRepository(GameType::class)->findOneBy(['name' => $data['name']]);
            if ($existing && $existing->getId() !== $gameType->getId()) {
                throw new \Exception("Another GameType with this name already exists.");
            }
            $gameType->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $gameType->setDescription($data['description']);
        }

        $gameType->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        return $gameType;
    }

    public function deleteGameType($idOrUuid): bool
    {
        $gameType = $this->getGameTypeInfo($idOrUuid);
        $this->entityManager->remove($gameType);
        $this->entityManager->flush();
        return true;
    }

    // ==========================================
    // CURRICULUM CRUD
    // ==========================================

    public function createCurriculum(array $data): Curriculum
    {
        if (empty($data['name'])) {
            throw new \Exception("Curriculum name is required.");
        }

        $existing = $this->entityManager->getRepository(Curriculum::class)->findOneBy(['name' => $data['name']]);
        if ($existing) {
            throw new \Exception("A Curriculum with this name already exists.");
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $curriculum = new Curriculum();
        $curriculum->setUuid($uuid)
                   ->setName($data['name'])
                   ->setDescription($data['description'] ?? null)
                   ->setMinAge(isset($data['min_age']) ? (int)$data['min_age'] : null)
                   ->setMaxAge(isset($data['max_age']) ? (int)$data['max_age'] : null);

        $this->entityManager->persist($curriculum);
        $this->entityManager->flush();

        return $curriculum;
    }

    public function listCurriculums(): array
    {
        return $this->entityManager->getRepository(Curriculum::class)->findAll();
    }

    public function getCurriculumInfo($idOrUuid): Curriculum
    {
        $repo = $this->entityManager->getRepository(Curriculum::class);
        $curriculum = null;

        if (is_numeric($idOrUuid)) {
            $curriculum = $repo->find((int) $idOrUuid);
        }
        if (!$curriculum) {
            $curriculum = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$curriculum) {
            $curriculum = $repo->findOneBy(['name' => $idOrUuid]);
        }

        if (!$curriculum) {
            throw new \Exception("Curriculum not found.");
        }

        return $curriculum;
    }

    public function updateCurriculum($idOrUuid, array $data): Curriculum
    {
        $curriculum = $this->getCurriculumInfo($idOrUuid);

        if (!empty($data['name'])) {
            $existing = $this->entityManager->getRepository(Curriculum::class)->findOneBy(['name' => $data['name']]);
            if ($existing && $existing->getId() !== $curriculum->getId()) {
                throw new \Exception("Another Curriculum with this name already exists.");
            }
            $curriculum->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $curriculum->setDescription($data['description']);
        }

        if (array_key_exists('min_age', $data)) {
            $curriculum->setMinAge($data['min_age'] !== null ? (int)$data['min_age'] : null);
        }

        if (array_key_exists('max_age', $data)) {
            $curriculum->setMaxAge($data['max_age'] !== null ? (int)$data['max_age'] : null);
        }

        $curriculum->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        return $curriculum;
    }

    public function deleteCurriculum($idOrUuid): bool
    {
        $curriculum = $this->getCurriculumInfo($idOrUuid);
        $this->entityManager->remove($curriculum);
        $this->entityManager->flush();
        return true;
    }

    // ==========================================
    // GAME CRUD
    // ==========================================

    public function createGame(array $data): Game
    {
        if (empty($data['title'])) {
            throw new \Exception("Game title is required.");
        }

        if (empty($data['game_type_id'])) {
            throw new \Exception("GameType identifier (game_type_id) is required.");
        }

        $gameType = $this->getGameTypeInfo($data['game_type_id']);

        $curriculum = null;
        if (!empty($data['curriculum_id'])) {
            $curriculum = $this->getCurriculumInfo($data['curriculum_id']);
        }

        // Unique Identifier handling
        $uniqueIdentifier = $data['unique_identifier'] ?? null;
        if (empty($uniqueIdentifier)) {
            $uniqueIdentifier = 'GAME-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }

        $existing = $this->entityManager->getRepository(Game::class)->findOneBy(['uniqueIdentifier' => $uniqueIdentifier]);
        if ($existing) {
            throw new \Exception("A Game with this unique identifier already exists.");
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $game = new Game();
        $game->setUuid($uuid)
             ->setUniqueIdentifier($uniqueIdentifier)
             ->setTitle($data['title'])
             ->setDescription($data['description'] ?? null)
             ->setGameType($gameType)
             ->setCurriculum($curriculum);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game;
    }

    public function listGames(): array
    {
        return $this->entityManager->getRepository(Game::class)->findAll();
    }

    public function getGameInfo($idOrUuid): Game
    {
        $repo = $this->entityManager->getRepository(Game::class);
        $game = null;

        if (is_numeric($idOrUuid)) {
            $game = $repo->find((int) $idOrUuid);
        }
        if (!$game) {
            $game = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$game) {
            $game = $repo->findOneBy(['uniqueIdentifier' => $idOrUuid]);
        }
        if (!$game) {
            $game = $repo->findOneBy(['title' => $idOrUuid]);
        }

        if (!$game) {
            throw new \Exception("Game not found.");
        }

        return $game;
    }

    public function updateGame($idOrUuid, array $data): Game
    {
        $game = $this->getGameInfo($idOrUuid);

        if (!empty($data['title'])) {
            $game->setTitle($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $game->setDescription($data['description']);
        }

        if (!empty($data['game_type_id'])) {
            $gameType = $this->getGameTypeInfo($data['game_type_id']);
            $game->setGameType($gameType);
        }

        if (array_key_exists('curriculum_id', $data)) {
            if (empty($data['curriculum_id'])) {
                $game->setCurriculum(null);
            } else {
                $curriculum = $this->getCurriculumInfo($data['curriculum_id']);
                $game->setCurriculum($curriculum);
            }
        }

        $game->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        return $game;
    }

    public function deleteGame($idOrUuid): bool
    {
        $game = $this->getGameInfo($idOrUuid);
        $this->entityManager->remove($game);
        $this->entityManager->flush();
        return true;
    }

    // ==========================================
    // GAMES COLLECTION CRUD
    // ==========================================

    public function createCollection(array $data): GamesCollection
    {
        if (empty($data['name'])) {
            throw new \Exception("Collection name is required.");
        }

        $existing = $this->entityManager->getRepository(GamesCollection::class)->findOneBy(['name' => $data['name']]);
        if ($existing) {
            throw new \Exception("A GamesCollection with this name already exists.");
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $collection = new GamesCollection();
        $collection->setUuid($uuid)
                   ->setName($data['name'])
                   ->setDescription($data['description'] ?? null);

        if (!empty($data['game_ids']) && is_array($data['game_ids'])) {
            foreach ($data['game_ids'] as $gameId) {
                $game = $this->getGameInfo($gameId);
                $collection->addGame($game);
            }
        }

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        return $collection;
    }

    public function listCollections(): array
    {
        return $this->entityManager->getRepository(GamesCollection::class)->findAll();
    }

    public function getCollectionInfo($idOrUuid): GamesCollection
    {
        $repo = $this->entityManager->getRepository(GamesCollection::class);
        $collection = null;

        if (is_numeric($idOrUuid)) {
            $collection = $repo->find((int) $idOrUuid);
        }
        if (!$collection) {
            $collection = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$collection) {
            $collection = $repo->findOneBy(['name' => $idOrUuid]);
        }

        if (!$collection) {
            throw new \Exception("GamesCollection not found.");
        }

        return $collection;
    }

    public function updateCollection($idOrUuid, array $data): GamesCollection
    {
        $collection = $this->getCollectionInfo($idOrUuid);

        if (!empty($data['name'])) {
            $existing = $this->entityManager->getRepository(GamesCollection::class)->findOneBy(['name' => $data['name']]);
            if ($existing && $existing->getId() !== $collection->getId()) {
                throw new \Exception("Another GamesCollection with this name already exists.");
            }
            $collection->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $collection->setDescription($data['description']);
        }

        if (isset($data['game_ids']) && is_array($data['game_ids'])) {
            $collection->getGames()->clear();
            foreach ($data['game_ids'] as $gameId) {
                $game = $this->getGameInfo($gameId);
                $collection->addGame($game);
            }
        }

        $collection->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        return $collection;
    }

    public function deleteCollection($idOrUuid): bool
    {
        $collection = $this->getCollectionInfo($idOrUuid);
        $this->entityManager->remove($collection);
        $this->entityManager->flush();
        return true;
    }
}
