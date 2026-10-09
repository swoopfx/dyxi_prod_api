<?php declare(strict_types=1);

namespace Game\Service;

use Doctrine\ORM\EntityManager;
use Game\Entity\Curriculum;
use Game\Entity\ToddlerGamesList;
use General\Service\RedisCacheService;
use Ramsey\Uuid\Uuid;
use Ward\Entity\Ward;

/**
 * Service class managing Curriculum CRUD operations, Ward-specific curriculum creation/recreation,
 * One-to-One Ward entity mapping, and single-key Redis caching under the 'dyxi_curriculum' namespace.
 */
class CurriculumService
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var RedisCacheService|null
     */
    private ?RedisCacheService $redisCacheService;

    /**
     * Cache TTL in seconds (0 = forever / no expiration).
     *
     * @var int
     */
    private int $cacheTtl = self::DEFAULT_CURRICULUM_CACHE_TTL;

    /**
     * Redis Cache Namespace for Curriculum services.
     */
    const CURRICULUM_CACHE_NAMESPACE = 'dyxi_curriculum';

    /**
     * Default Cache TTL (0 = forever / no expiration).
     */
    const DEFAULT_CURRICULUM_CACHE_TTL = 0;

    /**
     * CurriculumService constructor.
     *
     * @param EntityManager $entityManager Doctrine Entity Manager instance.
     * @param RedisCacheService|null $redisCacheService Optional Redis Cache Manager instance.
     */
    public function __construct(EntityManager $entityManager, ?RedisCacheService $redisCacheService = null)
    {
        $this->entityManager = $entityManager;
        $this->redisCacheService = $redisCacheService;
    }

    /**
     * Set the default Redis cache TTL for Curriculum operations.
     *
     * @param int $ttl Cache expiration time in seconds (0 = forever).
     * @return self
     */
    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Get the active default Redis cache TTL.
     *
     * @return int Cache expiration time in seconds (0 = forever).
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Helper to compute the single unique Redis cache key for a Curriculum (preferably using Ward UUID).
     *
     * @param Curriculum $curriculum Curriculum entity instance.
     * @param string|null $wardUuidParam Optional Ward UUID string passed in request.
     * @return string Single Redis cache key.
     */
    private function getSingleCacheKey(Curriculum $curriculum, ?string $wardUuidParam = null): string
    {
        if ($curriculum->getWard() && !empty($curriculum->getWard()->getUuid())) {
            return 'curriculum_info_ward_' . $curriculum->getWard()->getUuid();
        }
        if (!empty($wardUuidParam)) {
            return 'curriculum_info_ward_' . (string) $wardUuidParam;
        }
        return 'curriculum_info_' . $curriculum->getUuid();
    }

    /**
     * Normalizes selected games payload into structured list containing game_id, is_played, reason, and custom_variables.
     *
     * @param array $data Input payload
     * @return array List of normalized game objects
     */
    public function normalizeSelectedGames(array $data): array
    {
        $rawGames = $data['selected_games'] ?? $data['selectedGames'] ?? $data['games'] ?? null;
        if ($rawGames === null && isset($data['game_ids']) && is_array($data['game_ids'])) {
            $rawGames = $data['game_ids'];
        }

        if (!is_array($rawGames)) {
            return [];
        }

        $normalized = [];
        foreach ($rawGames as $item) {
            if (is_scalar($item)) {
                $normalized[] = [
                    'game_id' => is_numeric($item) ? (int) $item : (string) $item,
                    'is_played' => false,
                    'reason' => null,
                    'custom_variables' => null,
                ];
            } elseif (is_array($item)) {
                $gameId = $item['game_id'] ?? $item['id'] ?? $item['game_uuid'] ?? null;
                if ($gameId !== null) {
                    $normalized[] = [
                        'game_id' => is_numeric($gameId) ? (int) $gameId : (string) $gameId,
                        'is_played' => (bool) ($item['is_played'] ?? $item['played'] ?? false),
                        'reason' => isset($item['reason']) ? (string) $item['reason'] : null,
                        'custom_variables' => $item['custom_variables'] ?? $item['custom_variable'] ?? $item['custom_vars'] ?? null,
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * Creates a new Curriculum entity mapped One-to-One with a Ward entity, or recreates an existing one if 'force_recreate' is set.
     * Immediately primes Redis cache under a single unique key (preferring Ward UUID) with specified or default TTL.
     *
     * @param array $data Input payload containing:
     *                    - 'name' (string, required): Curriculum name.
     *                    - 'description' (string, optional): Detailed goals/summary.
     *                    - 'min_age' (int, optional): Minimum age limit.
     *                    - 'max_age' (int, optional): Maximum age limit.
     *                    - 'selected_games' (array, optional): Structured array of selected games.
     *                    - 'ward_uuid'|'ward_id'|'ward'|'identifier' (string|int, optional): Associated Ward entity ID or UUID.
     *                    - 'force_recreate' (bool, optional): Force-recreates/refreshes existing curriculum if set to true.
     *                    - 'ttl'|'cache_ttl' (int, optional): Expiration time in seconds (0 = forever).
     * @return Curriculum The created or updated Curriculum entity instance.
     * @throws \Exception If name is missing, UUID is invalid, or curriculum already exists without force_recreate.
     */
    public function createCurriculum(array $data): Curriculum
    {
        if (empty($data['name'])) {
            throw new \Exception('Curriculum name is required.');
        }

        $wardUuid = $data['ward_uuid'] ?? $data['ward_id'] ?? $data['ward'] ?? $data['identifier'] ?? null;
        $forceRecreate = !empty($data['force_recreate']);

        // Resolve TTL: if 'ttl' or 'cache_ttl' is passed in $data, use it; otherwise default to $this->cacheTtl (0 = forever)
        $ttl = isset($data['ttl']) ? (int) $data['ttl'] : (isset($data['cache_ttl']) ? (int) $data['cache_ttl'] : $this->cacheTtl);

        // Resolve One-to-One mapped Ward Entity if identifier is provided
        $wardEntity = null;
        if (!empty($wardUuid)) {
            $wardRepo = $this->entityManager->getRepository(Ward::class);
            if (is_numeric($wardUuid)) {
                $wardEntity = $wardRepo->find((int) $wardUuid);
            }
            if (!$wardEntity) {
                $wardEntity = $wardRepo->findOneBy(['uuid' => (string) $wardUuid]);
            }
        }

        $selectedGames = $this->normalizeSelectedGames($data);

        $repo = $this->entityManager->getRepository(Curriculum::class);
        $existing = null;

        if ($wardEntity) {
            $existing = $repo->findOneBy(['ward' => $wardEntity]);
        }
        if (!$existing && !empty($data['uuid'])) {
            $existing = $repo->findOneBy(['uuid' => (string) $data['uuid']]);
        }

        if ($existing) {
            if (!$forceRecreate) {
                throw new \Exception("A Curriculum for this Ward or UUID identifier already exists. Use 'force_recreate' => true to recreate.");
            }

            // Purge old single Redis cache key for this Curriculum
            if ($this->redisCacheService) {
                $ns = self::CURRICULUM_CACHE_NAMESPACE;
                $oldCacheKey = $this->getSingleCacheKey($existing, $wardUuid ? (string) $wardUuid : null);
                $this->redisCacheService->delete($oldCacheKey, $ns);
            }

            // Re-populate existing Curriculum entity attributes and Ward One-to-One relation
            $existing
                ->setName($data['name'])
                ->setDescription($data['description'] ?? null)
                ->setMinAge(isset($data['min_age']) ? (int) $data['min_age'] : null)
                ->setMaxAge(isset($data['max_age']) ? (int) $data['max_age'] : null)
                ->setSelectedGames($selectedGames)
                ->setWard($wardEntity)
                ->setUpdatedOn(new \DateTime());

            $curriculum = $existing;
            $this->entityManager->flush();
        } else {
            $uuid = $data['uuid'] ?? ($wardUuid && Uuid::isValid((string) $wardUuid) ? (string) $wardUuid : Uuid::uuid4()->toString());
            if (!Uuid::isValid($uuid)) {
                throw new \Exception('Invalid UUID format.');
            }

            $curriculum = new Curriculum();
            $curriculum
                ->setUuid($uuid)
                ->setName($data['name'])
                ->setDescription($data['description'] ?? null)
                ->setMinAge(isset($data['min_age']) ? (int) $data['min_age'] : null)
                ->setMaxAge(isset($data['max_age']) ? (int) $data['max_age'] : null)
                ->setSelectedGames($selectedGames)
                ->setWard($wardEntity);

            $this->entityManager->persist($curriculum);
            $this->entityManager->flush();
        }

        // Prime single Redis Cache key upon creation/recreation (preferring ward_uuid) with specified TTL (0 = forever)
        if ($this->redisCacheService) {
            $ns = self::CURRICULUM_CACHE_NAMESPACE;
            $singleKey = $this->getSingleCacheKey($curriculum, $wardUuid ? (string) $wardUuid : null);
            $this->redisCacheService->set($singleKey, $curriculum, $ttl, $ns);
        }

        return $curriculum;
    }

    /**
     * Explicitly force-recreates a Curriculum for a specific Ward entity/UUID.
     * Purges old single Redis cache key and re-primes fresh curriculum data.
     *
     * @param array $data Payload containing 'ward_uuid' (or 'ward') and curriculum attributes.
     * @return Curriculum The recreated Curriculum entity instance.
     * @throws \Exception If required payload attributes are missing or invalid.
     */
    public function recreateCurriculumForWard(array $data): Curriculum
    {
        $data['force_recreate'] = true;
        return $this->createCurriculum($data);
    }

    /**
     * Retrieves all curriculums directly from Doctrine ORM.
     *
     * @return array Array of Curriculum entity instances.
     */
    public function listCurriculums(): array
    {
        return $this->entityManager->getRepository(Curriculum::class)->findAll();
    }

    /**
     * Retrieves detailed Curriculum info by numeric primary key ID, Curriculum UUID, or Ward UUID.
     * Inspects Redis cache first using single key lookup before falling back to Doctrine ORM.
     *
     * @param int|string $idOrUuid Database primary key ID, Curriculum UUID, or Ward UUID.
     * @return Curriculum The resolved Curriculum entity.
     * @throws \Exception If no curriculum matches the given identifier.
     */
    public function getCurriculumInfo($idOrUuid): Curriculum
    {
        $ns = self::CURRICULUM_CACHE_NAMESPACE;

        if ($this->redisCacheService) {
            $cached = $this->redisCacheService->get('curriculum_info_ward_' . (string) $idOrUuid, $ns)
                ?? $this->redisCacheService->get('curriculum_info_' . (string) $idOrUuid, $ns);

            if ($cached instanceof Curriculum) {
                return $cached;
            }
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
            $wardRepo = $this->entityManager->getRepository(Ward::class);
            $ward = is_numeric($idOrUuid) ? $wardRepo->find((int) $idOrUuid) : $wardRepo->findOneBy(['uuid' => (string) $idOrUuid]);
            if ($ward) {
                $curriculum = $repo->findOneBy(['ward' => $ward]);
            }
        }

        if (!$curriculum) {
            throw new \Exception('Curriculum not found.');
        }

        if ($this->redisCacheService) {
            $cacheKey = $this->getSingleCacheKey($curriculum, is_string($idOrUuid) ? (string) $idOrUuid : null);
            $this->redisCacheService->set($cacheKey, $curriculum, $this->cacheTtl, $ns);
        }

        return $curriculum;
    }

    /**
     * Retrieves detailed Curriculum info directly using a Ward's UUID.
     *
     * @param string $wardUuid The associated Ward UUID.
     * @return Curriculum The resolved Curriculum entity.
     * @throws \Exception If no curriculum is found for the given Ward UUID.
     */
    public function getCurriculumByWardUuid(string $wardUuid): Curriculum
    {
        return $this->getCurriculumInfo($wardUuid);
    }

    /**
     * Updates an existing Curriculum entity by its identifier and purges single Redis cache key.
     *
     * @param int|string $idOrUuid Curriculum database ID or UUID to update.
     * @param array $data Attributes to update ('name', 'description', 'min_age', 'max_age', 'selected_games', 'ward_uuid').
     * @return Curriculum The updated Curriculum entity instance.
     * @throws \Exception If curriculum is not found.
     */
    public function updateCurriculum($idOrUuid, array $data): Curriculum
    {
        $curriculum = $this->getCurriculumInfo($idOrUuid);

        if (!empty($data['name'])) {
            $curriculum->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $curriculum->setDescription($data['description']);
        }

        if (array_key_exists('min_age', $data)) {
            $curriculum->setMinAge($data['min_age'] !== null ? (int) $data['min_age'] : null);
        }

        if (array_key_exists('max_age', $data)) {
            $curriculum->setMaxAge($data['max_age'] !== null ? (int) $data['max_age'] : null);
        }

        if (array_key_exists('selected_games', $data) || array_key_exists('selectedGames', $data) || array_key_exists('games', $data) || array_key_exists('game_ids', $data)) {
            $curriculum->setSelectedGames($this->normalizeSelectedGames($data));
        }

        $wardUuid = $data['ward_uuid'] ?? $data['ward_id'] ?? $data['ward'] ?? null;
        if (!empty($wardUuid)) {
            $wardRepo = $this->entityManager->getRepository(Ward::class);
            $wardEntity = is_numeric($wardUuid) ? $wardRepo->find((int) $wardUuid) : $wardRepo->findOneBy(['uuid' => (string) $wardUuid]);
            if ($wardEntity) {
                $curriculum->setWard($wardEntity);
            }
        }

        $curriculum->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        if ($this->redisCacheService) {
            $ns = self::CURRICULUM_CACHE_NAMESPACE;
            $cacheKey = $this->getSingleCacheKey($curriculum, is_string($idOrUuid) ? (string) $idOrUuid : null);
            $this->redisCacheService->delete($cacheKey, $ns);
            $this->redisCacheService->set($cacheKey, $curriculum, $this->cacheTtl, $ns);
        }

        return $curriculum;
    }

    /**
     * Updates the played indicator and custom variables for a specific game in a curriculum, reflecting in both DB and Redis.
     *
     * @param int|string $curriculumIdOrUuid Database ID, Curriculum UUID, or Ward UUID.
     * @param int|string $gameId Target Game ID or UUID.
     * @param bool $isPlayed Whether game was played.
     * @param mixed $customVariables Custom parameters passed/returned for actual game execution.
     * @param string|null $reason Optional updated reason.
     * @return Curriculum Updated Curriculum instance.
     */
    public function updateGamePlayedStatus($curriculumIdOrUuid, $gameId, bool $isPlayed = true, $customVariables = null, ?string $reason = null): Curriculum
    {
        $curriculum = $this->getCurriculumInfo($curriculumIdOrUuid);
        $selectedGames = $curriculum->getSelectedGames() ?? [];

        $updated = false;
        foreach ($selectedGames as &$item) {
            if (isset($item['game_id']) && (string) $item['game_id'] === (string) $gameId) {
                $item['is_played'] = $isPlayed;
                if ($customVariables !== null) {
                    $item['custom_variables'] = $customVariables;
                }
                if ($reason !== null) {
                    $item['reason'] = $reason;
                }
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $selectedGames[] = [
                'game_id' => is_numeric($gameId) ? (int) $gameId : (string) $gameId,
                'is_played' => $isPlayed,
                'reason' => $reason,
                'custom_variables' => $customVariables,
            ];
        }

        $curriculum->setSelectedGames($selectedGames);
        $curriculum->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        if ($this->redisCacheService) {
            $ns = self::CURRICULUM_CACHE_NAMESPACE;
            $cacheKey = $this->getSingleCacheKey($curriculum, is_string($curriculumIdOrUuid) ? (string) $curriculumIdOrUuid : null);
            $this->redisCacheService->delete($cacheKey, $ns);
            $this->redisCacheService->set($cacheKey, $curriculum, $this->cacheTtl, $ns);
        }

        return $curriculum;
    }


    /**
     * Deletes a Curriculum entity from the database and purges single Redis cache key.
     *
     * @param int|string $idOrUuid Curriculum database ID or UUID to delete.
     * @return bool True on successful deletion.
     * @throws \Exception If curriculum is not found.
     */
    public function deleteCurriculum($idOrUuid): bool
    {
        $curriculum = $this->getCurriculumInfo($idOrUuid);

        if ($this->redisCacheService) {
            $ns = self::CURRICULUM_CACHE_NAMESPACE;
            $cacheKey = $this->getSingleCacheKey($curriculum, is_string($idOrUuid) ? (string) $idOrUuid : null);
            $this->redisCacheService->delete($cacheKey, $ns);
        }

        $this->entityManager->remove($curriculum);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Flushes and invalidates all cached items stored under the 'dyxi_curriculum' Redis namespace.
     *
     * @return void
     */
    public function clearCurriculumCache(): void
    {
        if ($this->redisCacheService) {
            $this->redisCacheService->clearNamespace(self::CURRICULUM_CACHE_NAMESPACE);
        }
    }

    /**
     * Retrieves all ToddlerGamesList / Toddler Nest / Toddler Assessment entities.
     *
     * @return ToddlerGamesList[] Array of ToddlerGamesList entity instances.
     */
    public function getToddlerNestList(): array
    {
        return $this->entityManager->getRepository(ToddlerGamesList::class)->findAll();
    }

    /**
     * Alias for getToddlerNestList.
     *
     * @return ToddlerGamesList[]
     */
    public function getToddlerAssessmentList(): array
    {
        return $this->getToddlerNestList();
    }

    /**
     * Deactivates all existing ToddlerGamesList entities.
     *
     * @return void
     */
    public function deactivateAllToddlerGames(): void
    {
        $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
        $activeEntries = $repo->findBy(['isActive' => true]);
        foreach ($activeEntries as $item) {
            $item->setIsActive(false);
            $item->setUpdatedOn(new \DateTime());
        }
        $this->entityManager->flush();
    }

    /**
     * Retrieves the single active ToddlerGamesList / Toddler Assessment entity.
     * Falls back to the latest created entity if no entity is marked active.
     *
     * @return ToddlerGamesList|null The active ToddlerGamesList entity or null if none exist.
     */
    public function getActiveToddlerAssessment(): ?ToddlerGamesList
    {
        $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
        $active = $repo->findOneBy(['isActive' => true], ['id' => 'DESC']);
        if (!$active) {
            $active = $repo->findOneBy([], ['id' => 'DESC']);
        }
        return $active;
    }

    /**
     * Sets a specific ToddlerGamesList entity as active and deactivates all others.
     *
     * @param int|string $idOrUuid Database ID or UUID string.
     * @return ToddlerGamesList The updated active entity.
     * @throws \Exception If entity is not found.
     */
    public function setActiveToddlerAssessment($idOrUuid): ToddlerGamesList
    {
        $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
        $target = is_numeric($idOrUuid) ? $repo->find((int) $idOrUuid) : $repo->findOneBy(['uuid' => (string) $idOrUuid]);
        if (!$target) {
            throw new \Exception('Toddler Assessment entity not found.');
        }

        $this->deactivateAllToddlerGames();

        $target->setIsActive(true);
        $target->setUpdatedOn(new \DateTime());
        $this->entityManager->flush();

        return $target;
    }

    /**
     * Creates and persists a new ToddlerGamesList entity.
     * Ensures only ONE entity is active if 'is_active' is true (default true).
     *
     * @param array $data Input payload containing 'game_id', optional 'custom_config', and 'is_active'.
     * @return ToddlerGamesList The created entity.
     * @throws \Exception If game is not found.
     */
    public function createToddlerNestEntry(array $data): ToddlerGamesList
    {
        $gameId = $data['game_id'] ?? $data['gameId'] ?? null;
        if (empty($gameId)) {
            throw new \Exception("Parameter 'game_id' is required.");
        }

        $gameRepo = $this->entityManager->getRepository(\Game\Entity\Game::class);
        $game = is_numeric($gameId) ? $gameRepo->find((int) $gameId) : $gameRepo->findOneBy(['uuid' => (string) $gameId]);
        if (!$game) {
            $game = $gameRepo->findOneBy(['uniqueIdentifier' => (string) $gameId]);
        }

        if (!$game) {
            throw new \Exception("Game entity not found with identifier: " . $gameId);
        }

        $customConfig = null;
        if (!empty($data['custom_config'])) {
            $customConfig = is_array($data['custom_config']) ? $data['custom_config'] : json_decode($data['custom_config'], true);
        } elseif (!empty($data['customConfig'])) {
            $customConfig = is_array($data['customConfig']) ? $data['customConfig'] : json_decode($data['customConfig'], true);
        }

        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : (isset($data['isActive']) ? (bool) $data['isActive'] : true);
        if ($isActive) {
            $this->deactivateAllToddlerGames();
        }

        $toddlerGame = new ToddlerGamesList();
        $toddlerGame->setUuid(Uuid::uuid4()->toString());
        $toddlerGame->setGameId($game);
        $toddlerGame->setCustomeConfig($customConfig);
        $toddlerGame->setIsActive($isActive);
        $toddlerGame->setCreatedOn(new \DateTime());
        $toddlerGame->setUpdatedOn(new \DateTime());

        $this->entityManager->persist($toddlerGame);
        $this->entityManager->flush();

        return $toddlerGame;
    }

    /**
     * Alias for createToddlerNestEntry.
     *
     * @param array $data
     * @return ToddlerGamesList
     */
    public function createToddlerAssessmentEntry(array $data): ToddlerGamesList
    {
        return $this->createToddlerNestEntry($data);
    }

    /**
     * Redis Cache Namespace for Analytics services.
     */
    const ANALYTICS_CACHE_NAMESPACE = 'dyxi_analytics';

    /**
     * Retrieves the count of active concurrent players for a unique identifier directly from Redis cache.
     * Purely interacts with Redis cache under the 'dyxi_analytics' namespace without modifying or querying the database.
     *
     * @param string $identifier Unique identifier string (e.g. game unique_identifier, uuid, or custom name)
     * @return int Active concurrent players count
     */
    public function getActiveConcurrentPlayers(string $identifier = 'toddler_assessment'): int
    {
        if (!$this->redisCacheService) {
            return 0;
        }
        $key = 'active_concurrent_players_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $identifier);
        $val = $this->redisCacheService->get($key, self::ANALYTICS_CACHE_NAMESPACE);
        return is_numeric($val) ? (int) $val : 0;
    }

    /**
     * Updates (increments, decrements, sets, or gets) active concurrent player stats in Redis cache.
     * Purely interacts with Redis cache under the 'dyxi_analytics' namespace without affecting the relational database.
     *
     * @param string $identifier Unique identifier string
     * @param string $action 'get', 'increment', 'decrement', 'set', 'join', 'leave'
     * @param int $count Value/amount to modify or set
     * @return int Updated active concurrent player count
     */
    public function updateActiveConcurrentPlayers(string $identifier = 'toddler_assessment', string $action = 'get', int $count = 1): int
    {
        $current = $this->getActiveConcurrentPlayers($identifier);
        $action = strtolower(trim($action));

        switch ($action) {
            case 'increment':
            case 'join':
            case 'add':
                $newCount = max(0, $current + max(1, $count));
                break;
            case 'decrement':
            case 'leave':
            case 'remove':
            case 'sub':
                $newCount = max(0, $current - max(1, $count));
                break;
            case 'set':
                $newCount = max(0, $count);
                break;
            case 'get':
            default:
                $newCount = $current;
                break;
        }

        if ($this->redisCacheService && $action !== 'get') {
            $key = 'active_concurrent_players_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $identifier);
            $this->redisCacheService->set($key, $newCount, 86400, self::ANALYTICS_CACHE_NAMESPACE);
        }

        return $newCount;
    }

    /**
     * Flushes and invalidates all cached metrics and counters stored under the 'dyxi_analytics' Redis namespace.
     * Can be executed at any instance to restart or reset analytics without affecting other namespaces (dyxi_curriculum, dyxi_general, etc.).
     *
     * @return bool True if flushed successfully.
     */
    public function clearAnalyticsCache(): bool
    {
        if ($this->redisCacheService) {
            return $this->redisCacheService->clearNamespace(self::ANALYTICS_CACHE_NAMESPACE);
        }
        return false;
    }
}

