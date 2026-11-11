<?php

namespace Game\Service;

use Doctrine\ORM\EntityManager;
use Game\Entity\GameType;
use Game\Entity\Curriculum;
use Game\Entity\Game;
use Game\Entity\GamesCollection;
use Ramsey\Uuid\Uuid;

use Game\Service\CurriculumService;
use General\Service\RedisCacheService;
use General\Service\BigQueryService;

class GameService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RedisCacheService|null
     */
    private ?RedisCacheService $redisCacheService;

    /**
     * @var CurriculumService|null
     */
    private ?CurriculumService $curriculumService;

    /**
     * @var BigQueryService|null
     */
    private ?BigQueryService $bigQueryService;

    /**
     * @var GameBigQueryService|null
     */
    private ?GameBigQueryService $gameBigQueryService;

    const CURRICULUM_CACHE_NAMESPACE = 'dyxi_curriculum';

    /**
     * Predefined schemas for known BigQuery game telemetry and assessment tables.
     */
    private const KNOWN_TABLE_SCHEMAS = [
        'recog_shape_recog' => [
            'session_id'            => 'STRING',
            'game_id'               => 'STRING',
            'game_type'             => 'STRING',
            'game_category'         => 'STRING',
            'game_program'          => 'STRING',
            'user_id'               => 'STRING',
            'problem_solving_index' => 'FLOAT',
            'creative_index'        => 'FLOAT',
            'average_time_correct'  => 'FLOAT',
            'average_time_failed'   => 'FLOAT',
            'total_game_time'       => 'FLOAT',
            'total_correct'         => 'INTEGER',
            'total_failed'          => 'INTEGER',
            'start_time'            => 'STRING',
            'input_events'          => 'JSON',
            'match_events'          => 'JSON',
        ],
        'game_session_telemetry' => [
            'session_id'            => 'STRING',
            'game_id'               => 'STRING',
            'user_id'               => 'STRING',
            'age'                   => 'FLOAT',
            'game_type'             => 'STRING',
            'score'                 => 'FLOAT',
            'duration'              => 'FLOAT',
            'events'                => 'JSON',
            'created_at'            => 'STRING',
        ],
    ];

    /**
     * GameService constructor.
     *
     * @param EntityManager $entityManager
     * @param RedisCacheService|null $redisCacheService
     * @param CurriculumService|null $curriculumService
     * @param BigQueryService|null $bigQueryService
     * @param GameBigQueryService|null $gameBigQueryService
     */
    public function __construct(
        EntityManager $entityManager,
        ?RedisCacheService $redisCacheService = null,
        ?CurriculumService $curriculumService = null,
        ?BigQueryService $bigQueryService = null,
        ?GameBigQueryService $gameBigQueryService = null
    ) {
        $this->entityManager = $entityManager;
        $this->redisCacheService = $redisCacheService;
        $this->curriculumService = $curriculumService;
        $this->bigQueryService = $bigQueryService;
        $this->gameBigQueryService = $gameBigQueryService;
    }

    public function getBigQueryService(): ?BigQueryService
    {
        return $this->bigQueryService;
    }

    public function setBigQueryService(BigQueryService $bigQueryService): self
    {
        $this->bigQueryService = $bigQueryService;
        return $this;
    }

    public function getGameBigQueryService(): ?GameBigQueryService
    {
        return $this->gameBigQueryService;
    }

    public function setGameBigQueryService(GameBigQueryService $gameBigQueryService): self
    {
        $this->gameBigQueryService = $gameBigQueryService;
        return $this;
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
    // CURRICULUM DELEGATION TO CurriculumService
    // ==========================================

    public function createCurriculum(array $data): Curriculum
    {
        if ($this->curriculumService) {
            return $this->curriculumService->createCurriculum($data);
        }
        throw new \Exception("CurriculumService is unavailable.");
    }

    public function listCurriculums(): array
    {
        if ($this->curriculumService) {
            return $this->curriculumService->listCurriculums();
        }
        return $this->entityManager->getRepository(Curriculum::class)->findAll();
    }

    public function getCurriculumInfo($idOrUuid): Curriculum
    {
        if ($this->curriculumService) {
            return $this->curriculumService->getCurriculumInfo($idOrUuid);
        }
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
        if ($this->curriculumService) {
            return $this->curriculumService->updateCurriculum($idOrUuid, $data);
        }
        throw new \Exception("CurriculumService is unavailable.");
    }

    public function deleteCurriculum($idOrUuid): bool
    {
        if ($this->curriculumService) {
            return $this->curriculumService->deleteCurriculum($idOrUuid);
        }
        return false;
    }

    public function clearCurriculumCache(): void
    {
        if ($this->curriculumService) {
            $this->curriculumService->clearCurriculumCache();
        }
    }

    public function getCurriculumWithFilters($idOrUuid): array
    {
        if ($this->curriculumService) {
            return $this->curriculumService->getCurriculumWithFilters($idOrUuid);
        }
        return [];
    }

    public function getCurriculumByWardUuid(string $wardUuid): Curriculum
    {
        if ($this->curriculumService) {
            return $this->curriculumService->getCurriculumByWardUuid($wardUuid);
        }
        return $this->getCurriculumInfo($wardUuid);
    }

    public function getCurriculumFiltersByWardUuid(string $wardUuid): array
    {
        if ($this->curriculumService) {
            return $this->curriculumService->getCurriculumFiltersByWardUuid($wardUuid);
        }
        return $this->getCurriculumWithFilters($wardUuid);
    }

    public function recreateCurriculumForWard(array $data): Curriculum
    {
        if ($this->curriculumService) {
            return $this->curriculumService->recreateCurriculumForWard($data);
        }
        $data['force_recreate'] = true;
        return $this->createCurriculum($data);
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
             ->setSummary($data['summary'] ?? null)
             ->setDescription($data['description'] ?? null)
             ->setGameType($gameType)
             ->setCurriculum($curriculum);

        if (!empty($data['target_tags']) && is_array($data['target_tags'])) {
            $tagRepo = $this->entityManager->getRepository(\Game\Entity\TargetTags::class);
            foreach ($data['target_tags'] as $tagInput) {
                $tag = null;
                if (is_numeric($tagInput)) {
                    $tag = $tagRepo->find((int) $tagInput);
                }
                if (!$tag) {
                    $tag = $tagRepo->findOneBy(['uuid' => $tagInput]);
                }
                if (!$tag) {
                    $tag = $tagRepo->findOneBy(['name' => $tagInput]);
                }
                if ($tag) {
                    $game->addTargetTag($tag);
                }
            }
        }

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

        if (array_key_exists('summary', $data)) {
            $game->setSummary($data['summary']);
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

        if (isset($data['target_tags']) && is_array($data['target_tags'])) {
            $game->getTargetTags()->clear();
            $tagRepo = $this->entityManager->getRepository(\Game\Entity\TargetTags::class);
            foreach ($data['target_tags'] as $tagInput) {
                $tag = null;
                if (is_numeric($tagInput)) {
                    $tag = $tagRepo->find((int) $tagInput);
                }
                if (!$tag) {
                    $tag = $tagRepo->findOneBy(['uuid' => $tagInput]);
                }
                if (!$tag) {
                    $tag = $tagRepo->findOneBy(['name' => $tagInput]);
                }
                if ($tag) {
                    $game->addTargetTag($tag);
                }
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

    // ==========================================
    // BIGQUERY GENERIC INGESTION
    // ==========================================

    /**
     * Generic ingestion handler for posting data to any BigQuery table.
     * Enforces mandatory validation: 'age' (numeric and >= 0) and 'user_id' must be present and not empty.
     * Extracts dynamic raw JSON payloads and inserts only the relevant fields defined in target BigQuery table schemas.
     *
     * @param string $tableName Target BigQuery table name.
     * @param array|object|string $data Single record, array of records, or raw JSON string.
     * @param string|null $datasetId Optional target dataset ID.
     * @param mixed $identity Authenticated user identity payload.
     * @return array Ingestion result details.
     * @throws \InvalidArgumentException If validation fails.
     * @throws \RuntimeException If BigQueryService is unavailable.
     * @throws \Exception On BigQuery API error.
     */
    public function postBigQueryData(string $tableName, $data, ?string $datasetId = null, $identity = null): array
    {
        $bqService = $this->getBigQueryService();
        if (!$bqService) {
            throw new \RuntimeException("BigQuery service is not initialized or configured.");
        }

        $tableName = trim($tableName);
        if (empty($tableName) || !preg_match('/^[a-zA-Z0-9_]{1,1024}$/', $tableName)) {
            throw new \InvalidArgumentException("Invalid table name. Must contain only alphanumeric characters and underscores.");
        }

        $targetDataset = !empty($datasetId) ? trim($datasetId) : $bqService->getDatasetId();
        if (!preg_match('/^[a-zA-Z0-9_]{1,1024}$/', $targetDataset)) {
            throw new \InvalidArgumentException("Invalid dataset ID format.");
        }

        // If raw JSON string is passed, extract and decode it
        if (is_string($data)) {
            $decoded = json_decode(trim($data), true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                $data = (array) $decoded;
            } else {
                throw new \InvalidArgumentException("The data parameter contains invalid JSON: " . json_last_error_msg());
            }
        }

        // If data is an array with JSON string elements, decode each element
        if (is_array($data)) {
            foreach ($data as $k => $item) {
                if (is_string($item)) {
                    $decodedItem = json_decode(trim($item), true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedItem) || is_object($decodedItem))) {
                        $data[$k] = (array) $decodedItem;
                    }
                }
            }
        }

        if (empty($data) || (!is_array($data) && !is_object($data))) {
            throw new \InvalidArgumentException("Data payload cannot be empty and must be an object, array of objects, or valid JSON string.");
        }

        $isBatch = isset($data[0]) && is_array($data[0]);
        $rows = $isBatch ? $data : [$data];

        // Mandatory validation: every record must contain valid 'age' (numeric and >= 0) and non-empty 'user_id'
        foreach ($rows as $idx => &$row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(sprintf("Record at index %d must be an object/associative array.", $idx));
            }

            // 1. Validate 'age' is present, not empty, numeric and non-negative
            if (!array_key_exists('age', $row) || $row['age'] === null || $row['age'] === '' || (is_string($row['age']) && trim((string) $row['age']) === '')) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d is missing required field 'age' or it is empty.",
                    $idx
                ));
            }
            if (!is_numeric($row['age']) || (float) $row['age'] < 0) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d field 'age' must be a valid positive number.",
                    $idx
                ));
            }

            // 2. Validate 'user_id' is present and not empty
            $userId = $row['user_id'] ?? $row['user_uuid'] ?? null;
            if ($userId === null || trim((string) $userId) === '') {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d is missing required field 'user_id' or it is empty.",
                    $idx
                ));
            }

            // Harmonize user identifier across records
            $row['user_id'] = (string) $userId;
            $row['user_uuid'] = (string) ($row['user_uuid'] ?? $userId);
        }
        unset($row);

        // Switch condition to route implementation based on the table name
        switch ($tableName) {
            case 'recog_shape_recog':
                return $this->postRecogShapeRecog($rows, $targetDataset, $identity, $isBatch);

            case 'game_session_telemetry':
            case 'game_analytics':
                return $this->postGameSessionTelemetry($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'toddler_assessment':
            case 'toddler_games':
                return $this->postToddlerAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'dyslexia_assessment':
            case 'dyslexia_subtypes':
                return $this->postDyslexiaAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'adhd_assessment':
            case 'adhd_scale':
                return $this->postAdhdAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'dyscalculia_assessment':
                return $this->postDyscalculiaAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            default:
                // Generic handler that conforms to ANY arbitrary table name and dynamic schema sent to it
                return $this->postGenericTable($tableName, $rows, $targetDataset, $identity, $isBatch);
        }
    }

    /**
     * Privately defined handler for the 'recog_shape_recog' cognitive assessment table.
     */
    private function postRecogShapeRecog(array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable('recog_shape_recog', $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for game session telemetry & analytics tables.
     */
    private function postGameSessionTelemetry(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for toddler assessment tables.
     */
    private function postToddlerAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for dyslexia assessment tables.
     */
    private function postDyslexiaAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for ADHD assessment tables.
     */
    private function postAdhdAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for dyscalculia assessment tables.
     */
    private function postDyscalculiaAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined generic handler that extracts the relevant fields according to the target BigQuery table schema.
     */
    private function postGenericTable(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        $bqService = $this->getBigQueryService();
        $bqService->ensureTableExists($tableName, $datasetId);

        $tableMetadata = $bqService->getTable($tableName, $datasetId);
        $allowedFields = null;
        $fieldTypes = [];
        if (!empty($tableMetadata['schema']['fields']) && is_array($tableMetadata['schema']['fields'])) {
            $allowedFields = [];
            foreach ($tableMetadata['schema']['fields'] as $field) {
                if (!empty($field['name'])) {
                    $allowedFields[$field['name']] = true;
                    $fieldTypes[$field['name']] = strtoupper($field['type'] ?? 'STRING');
                }
            }
        } elseif (isset(self::KNOWN_TABLE_SCHEMAS[$tableName])) {
            $allowedFields = [];
            foreach (self::KNOWN_TABLE_SCHEMAS[$tableName] as $fieldName => $fieldType) {
                $allowedFields[$fieldName] = true;
                $fieldTypes[$fieldName] = strtoupper($fieldType);
            }
        }

        $sanitizedRows = [];
        foreach ($rows as $row) {
            // Auto-generate session_id or uuid if missing and defined in table schema
            if (empty($row['session_id']) && ($allowedFields === null || isset($allowedFields['session_id']))) {
                $row['session_id'] = Uuid::uuid4()->toString();
            }
            if (empty($row['uuid']) && ($allowedFields === null || isset($allowedFields['uuid']))) {
                $row['uuid'] = Uuid::uuid4()->toString();
            }

            // Harmonize user_id and user_uuid based on schema expectations
            if (empty($row['user_id']) && !empty($row['user_uuid'])) {
                $row['user_id'] = (string) $row['user_uuid'];
            }
            if (empty($row['user_uuid']) && !empty($row['user_id'])) {
                $row['user_uuid'] = (string) $row['user_id'];
            }

            // Auto-generate start_time / created_at if missing and present in schema
            if (empty($row['start_time']) && ($allowedFields === null || isset($allowedFields['start_time']))) {
                $row['start_time'] = gmdate('Y-m-d\TH:i:s\Z');
            }
            if (empty($row['created_at']) && ($allowedFields === null || isset($allowedFields['created_at']))) {
                $row['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
            }

            $cleanRow = [];
            foreach ($row as $col => $val) {
                // If schema is known, extract only fields declared in the BigQuery table
                if ($allowedFields !== null && !isset($allowedFields[$col])) {
                    continue;
                }

                $type = $fieldTypes[$col] ?? null;

                if ($type === 'JSON' || is_array($val) || is_object($val)) {
                    $cleanRow[$col] = is_string($val) ? $val : json_encode($val);
                } elseif ($type === 'INTEGER' || $type === 'INT64') {
                    $cleanRow[$col] = $val !== null ? (int) $val : null;
                } elseif ($type === 'FLOAT' || $type === 'FLOAT64' || $type === 'NUMERIC') {
                    $cleanRow[$col] = $val !== null ? (float) $val : null;
                } elseif ($type === 'BOOLEAN' || $type === 'BOOL') {
                    $cleanRow[$col] = $val !== null ? (bool) $val : null;
                } else {
                    $cleanRow[$col] = $val !== null ? (is_array($val) ? json_encode($val) : (string) $val) : null;
                }
            }

            if (empty($cleanRow)) {
                // If column filtering stripped everything, pass original row with JSON-encoded arrays
                $fallbackRow = [];
                foreach ($row as $k => $v) {
                    $fallbackRow[$k] = (is_array($v) || is_object($v)) ? json_encode($v) : $v;
                }
                $cleanRow = $fallbackRow;
            }

            $sanitizedRows[] = $cleanRow;
        }

        $insertPayload = $isBatch ? $sanitizedRows : $sanitizedRows[0];
        $result = $bqService->create($tableName, $insertPayload, $datasetId);

        return [
            'table'          => $tableName,
            'dataset'        => $datasetId,
            'inserted_count' => $result['inserted_count'] ?? count($sanitizedRows),
            'method'         => $result['method'] ?? 'streaming_insert',
            'session_id'     => $sanitizedRows[0]['session_id'] ?? null,
            'records'        => $isBatch ? $sanitizedRows : $sanitizedRows[0],
        ];
    }
}
