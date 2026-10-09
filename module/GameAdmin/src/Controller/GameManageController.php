<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameManageController.php
 * ============================================================================
 * Description: Controller responsible for game catalog management, server-side
 * search filtering, pagination, game entity creation (with multiple .md file
 * content extraction and Gemini AI summary/tag generation), viewing, and editing.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller;

use Doctrine\ORM\EntityManager;
use Game\Entity\Curriculum;
use Game\Entity\Game;
use Game\Entity\GameType;
use Game\Entity\ToddlerGamesList;
use GameAdmin\Form\GameForm;
use GameAdmin\Form\ToddlerAssessmentForm;
use GameAdmin\Form\ToddlerNestForm;
use GameAdmin\Service\GeminiService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Ramsey\Uuid\Uuid;

/**
 * Controller class for game management, markdown file processing, view/edit, search filtering, and pagination.
 */
class GameManageController extends AbstractActionController
{
    /**
     * Doctrine Entity Manager instance.
     */
    private ?EntityManager $entityManager;

    /**
     * GameManageController Constructor.
     *
     * @param EntityManager|null $entityManager Database entity manager.
     */
    public function __construct(?EntityManager $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Displays the game catalog with database-level Search Filtering and Pagination (/game-admin/games).
     *
     * @return ViewModel
     */
    public function gamesAction(): ViewModel
    {
        $request    = $this->getRequest();
        $q          = trim((string) $this->params()->fromQuery('q', ''));
        $column     = trim((string) $this->params()->fromQuery('column', 'all'));
        $gameTypeId = trim((string) $this->params()->fromQuery('game_type_id', ''));
        $page       = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit      = max(1, min(50, (int) $this->params()->fromQuery('limit', 10)));

        $result     = $this->getFilteredGamesFromDb($q, $column, $gameTypeId, $page, $limit);
        $games      = $result['games'];
        $total      = $result['total'];

        $totalPages = max(1, (int) ceil($total / $limit));
        $page       = min($page, $totalPages);

        $form = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());

        $viewModel = new ViewModel([
            'activeNav'       => 'games',
            'currentUser'     => $this->getActiveUser(),
            'games'           => $games,
            'form'            => $form,
            'gameTypeOptions' => $this->getGameTypeOptions(),
            'pagination'      => [
                'page'         => $page,
                'limit'        => $limit,
                'total'        => $total,
                'totalPages'   => $totalPages,
                'q'            => $q,
                'column'       => $column,
                'game_type_id' => $gameTypeId,
            ],
        ]);

        $viewModel->setTemplate('game-admin/game-admin/games');
        return $viewModel;
    }

    /**
     * Renders and processes the Game Entity Creation Form (/game-admin/games/create).
     *
     * Accepts text descriptions AND multiple uploaded .md files, extracts file text,
     * relays combined content to Gemini AI for summary generation and tag extraction.
     *
     * @return ViewModel|\Laminas\Http\Response
     */
    public function createGameAction()
    {
        $request        = $this->getRequest();
        $form           = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());
        $successMessage = null;
        $errorMessage   = null;

        if ($request->isPost()) {
            $postData = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            $form->setData($postData);

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $game = new Game();
                    $game->setUuid(Uuid::uuid4()->toString());
                    $game->setTitle($data['title']);
                    $game->setUniqueIdentifier($data['uniqueIdentifier']);
                    $game->setGameAbsoluteUrl($data['gameAbsoluteUrl']);
                    $game->setCreatedOn(new \DateTime());
                    $game->setUpdatedOn(new \DateTime());

                    // Extract text content from all uploaded .md files
                    $mdExtractedText = $this->extractMdFilesContent($request->getFiles()->toArray());
                    
                    $manualDescription = (string) ($data['description'] ?? '');
                    $fullDescription   = trim($manualDescription . "\n\n" . $mdExtractedText);

                    $game->setDescription($fullDescription ?: null);

                    // Integrate Gemini AI Service for summary generation & keyword tag extraction
                    $geminiService = new GeminiService();

                    // 1. Generate AI Summary from description + .md content
                    $summary = $geminiService->summarizeDescription($fullDescription);
                    $game->setSummary($summary);

                    // 2. Extract keyword tags (Early Childhood Education, Neurodevelopmental Disorders, Child Education)
                    $extractedTags = $geminiService->extractSearchableTags($fullDescription);
                    if (!empty($data['tags'])) {
                        $userTags      = array_map('trim', explode(',', $data['tags']));
                        $extractedTags = array_values(array_unique(array_merge($userTags, $extractedTags)));
                    }
                    $game->setTags($extractedTags);

                    if (!empty($data['customConfig'])) {
                        $configDecoded = json_decode($data['customConfig'], true);
                        if (is_array($configDecoded)) {
                            $game->setCustomConfig($configDecoded);
                        }
                    }

                    // Persist to Doctrine ORM database
                    if ($this->entityManager !== null) {
                        if (!empty($data['gameTypeId'])) {
                            $gameType = $this->entityManager->find(GameType::class, (int)$data['gameTypeId']);
                            if ($gameType !== null) {
                                $game->setGameType($gameType);
                            }
                        }
                        if (!empty($data['curriculumId'])) {
                            $curriculum = $this->entityManager->find(Curriculum::class, (int)$data['curriculumId']);
                            if ($curriculum !== null) {
                                $game->setCurriculum($curriculum);
                            }
                        }

                        $this->entityManager->persist($game);
                        $this->entityManager->flush();
                    }

                    $tagListStr     = is_array($extractedTags) ? implode(', ', $extractedTags) : (string)$extractedTags;
                    $successMessage = sprintf(
                        'Game entity "%s" created successfully! Gemini AI processed content from description & .md files, generated the summary, and extracted %d tags: [%s]',
                        $data['title'],
                        is_array($extractedTags) ? count($extractedTags) : 1,
                        $tagListStr
                    );

                    $form = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());
                } catch (\Throwable $e) {
                    $errorMessage = 'Failed to persist Game entity: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'Validation failed. Please correct highlighted errors below.';
            }
        }

        $viewModel = new ViewModel([
            'activeNav'      => 'games',
            'currentUser'    => $this->getActiveUser(),
            'form'           => $form,
            'successMessage' => $successMessage,
            'errorMessage'   => $errorMessage,
        ]);

        $viewModel->setTemplate('game-admin/game-admin/create-game');
        return $viewModel;
    }

    /**
     * Renders detailed specification view for a specific Game entity (/game-admin/games/view/:id).
     *
     * @return ViewModel
     */
    public function viewGameAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $gameData = null;

        if ($this->entityManager !== null && $id > 0) {
            try {
                /** @var Game|null $entity */
                $entity = $this->entityManager->find(Game::class, $id);
                if ($entity !== null) {
                    $gameData = [
                        'id'               => $entity->getId(),
                        'uuid'             => $entity->getUuid(),
                        'uniqueIdentifier' => $entity->getUniqueIdentifier(),
                        'title'            => $entity->getTitle(),
                        'summary'          => $entity->getSummary(),
                        'description'      => $entity->getDescription(),
                        'tags'             => $entity->getTags(),
                        'gameType'         => $entity->getGameType() ? $entity->getGameType()->getName() : 'Interactive',
                        'curriculum'       => $entity->getCurriculum() ? $entity->getCurriculum()->getName() : 'None',
                        'gameAbsoluteUrl'  => $entity->getGameAbsoluteUrl(),
                        'customConfig'     => $entity->getCustomConfig(),
                        'createdOn'        => $entity->getCreatedOn() ? $entity->getCreatedOn()->format('Y-m-d H:i:s') : 'N/A',
                        'updatedOn'        => $entity->getUpdatedOn() ? $entity->getUpdatedOn()->format('Y-m-d H:i:s') : 'N/A',
                        'status'           => 'Active',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore exception
            }
        }

        $viewModel = new ViewModel([
            'activeNav'   => 'games',
            'currentUser' => $this->getActiveUser(),
            'game'        => $gameData,
        ]);

        $viewModel->setTemplate('game-admin/game-admin/view-game');
        return $viewModel;
    }

    /**
     * Renders and processes the Edit Game Entity Form (/game-admin/games/edit/:id).
     *
     * @return ViewModel|\Laminas\Http\Response
     */
    public function editGameAction()
    {
        $id      = (int) $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $form    = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());

        $existingEntity = null;
        if ($this->entityManager !== null && $id > 0) {
            try {
                $existingEntity = $this->entityManager->find(Game::class, $id);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $successMessage = null;
        $errorMessage   = null;

        // Pre-fill form if GET request
        if (!$request->isPost() && $existingEntity !== null) {
            $form->setData([
                'title'            => $existingEntity->getTitle(),
                'uniqueIdentifier' => $existingEntity->getUniqueIdentifier(),
                'gameTypeId'       => $existingEntity->getGameType() ? (string)$existingEntity->getGameType()->getId() : '1',
                'curriculumId'     => $existingEntity->getCurriculum() ? (string)$existingEntity->getCurriculum()->getId() : '',
                'gameAbsoluteUrl'  => $existingEntity->getGameAbsoluteUrl(),
                'tags'             => $existingEntity->getTags(),
                'description'      => $existingEntity->getDescription(),
                'customConfig'     => $existingEntity->getCustomConfig() ? json_encode($existingEntity->getCustomConfig(), JSON_PRETTY_PRINT) : '',
            ]);
        }

        // Process Edit Form Submission
        if ($request->isPost()) {
            $postData = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($postData);

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $game = $existingEntity ?? new Game();
                    if (!$game->getUuid()) {
                        $game->setUuid(Uuid::uuid4()->toString());
                        $game->setCreatedOn(new \DateTime());
                    }
                    $game->setUpdatedOn(new \DateTime());

                    $game->setTitle($data['title']);
                    $game->setUniqueIdentifier($data['uniqueIdentifier']);
                    $game->setGameAbsoluteUrl($data['gameAbsoluteUrl']);

                    // Read uploaded .md files
                    $mdExtractedText   = $this->extractMdFilesContent($request->getFiles()->toArray());
                    $manualDescription = (string) ($data['description'] ?? '');
                    $fullDescription   = trim($manualDescription . "\n\n" . $mdExtractedText);
                    $game->setDescription($fullDescription ?: null);

                    // Re-run Gemini AI for summary & tags
                    $geminiService = new GeminiService();
                    $summary       = $geminiService->summarizeDescription($fullDescription);
                    $game->setSummary($summary);

                    $extractedTags = $geminiService->extractSearchableTags($fullDescription);
                    if (!empty($data['tags'])) {
                        $userTags      = array_map('trim', explode(',', $data['tags']));
                        $extractedTags = array_values(array_unique(array_merge($userTags, $extractedTags)));
                    }
                    $game->setTags($extractedTags);

                    if (!empty($data['customConfig'])) {
                        $configDecoded = json_decode($data['customConfig'], true);
                        if (is_array($configDecoded)) {
                            $game->setCustomConfig($configDecoded);
                        }
                    }

                    if ($this->entityManager !== null) {
                        if (!empty($data['gameTypeId'])) {
                            $gameType = $this->entityManager->find(GameType::class, (int)$data['gameTypeId']);
                            if ($gameType !== null) {
                                $game->setGameType($gameType);
                            }
                        }
                        if (!empty($data['curriculumId'])) {
                            $curriculum = $this->entityManager->find(Curriculum::class, (int)$data['curriculumId']);
                            if ($curriculum !== null) {
                                $game->setCurriculum($curriculum);
                            }
                        }

                        $this->entityManager->persist($game);
                        $this->entityManager->flush();
                    }

                    $successMessage = sprintf('Game entity "%s" updated successfully with refreshed Gemini AI summaries & tags!', $data['title']);
                } catch (\Throwable $e) {
                    $errorMessage = 'Failed to update Game entity: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'Validation failed. Please check highlighted fields below.';
            }
        }

        $viewModel = new ViewModel([
            'activeNav'      => 'games',
            'currentUser'    => $this->getActiveUser(),
            'form'           => $form,
            'gameId'         => $id,
            'successMessage' => $successMessage,
            'errorMessage'   => $errorMessage,
        ]);

        $viewModel->setTemplate('game-admin/game-admin/edit-game');
        return $viewModel;
    }

    /**
     * Reads and extracts plain text content from multiple uploaded .md files.
     *
     * @param array $filesArray $_FILES payload.
     * @return string Concatenated extracted markdown content.
     */
    private function extractMdFilesContent(array $filesArray): string
    {
        if (empty($filesArray['mdFiles'])) {
            return '';
        }

        $extractedContents = [];
        $fileEntry         = $filesArray['mdFiles'];

        // Handle multiple file upload array structure
        if (is_array($fileEntry) && isset($fileEntry['tmp_name'])) {
            $tmpNames = is_array($fileEntry['tmp_name']) ? $fileEntry['tmp_name'] : [$fileEntry['tmp_name']];
            $names    = is_array($fileEntry['name']) ? $fileEntry['name'] : [$fileEntry['name']];
            $errors   = is_array($fileEntry['error']) ? $fileEntry['error'] : [$fileEntry['error']];

            foreach ($tmpNames as $idx => $tmpPath) {
                $error    = $errors[$idx] ?? UPLOAD_ERR_NO_FILE;
                $filename = $names[$idx] ?? 'file.md';

                if ($error === UPLOAD_ERR_OK && !empty($tmpPath) && is_uploaded_file($tmpPath)) {
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (in_array($ext, ['md', 'markdown', 'txt'])) {
                        $content = file_get_contents($tmpPath);
                        if ($content !== false && !empty(trim($content))) {
                            $extractedContents[] = "--- Content from " . htmlspecialchars($filename) . " ---\n" . trim($content);
                        }
                    }
                }
            }
        }

        return implode("\n\n", $extractedContents);
    }

    /**
     * Retrieves current user session payload.
     *
     * @return array
     */
    private function getActiveUser(): array
    {
        $session = new Container('GameAdmin');
        return $session->user ?? [
            'username'  => 'Game Administrator',
            'email'     => 'admin@dyxi.internal',
            'role_name' => 'SuperAdmin',
        ];
    }

    /**
     * Executes database-level QueryBuilder search filtering across searchable Game database columns.
     *
     * @param string|null $searchQuery Search term
     * @param string|null $columnFilter Target database column ('title', 'unique_identifier', 'uuid', 'tags', 'summary', 'url', 'all')
     * @param string|null $gameTypeId GameType ID filter
     * @param int $page Active page number
     * @param int $limit Items per page
     * @return array Array containing 'games' list and 'total' count.
     */
    private function getFilteredGamesFromDb(?string $searchQuery, ?string $columnFilter, ?string $gameTypeId, int $page, int $limit): array
    {
        if ($this->entityManager === null) {
            return ['games' => [], 'total' => 0];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('g', 'gt')
           ->from(Game::class, 'g')
           ->leftJoin('g.gameType', 'gt')
           ->orderBy('g.id', 'DESC');

        $countQb = $this->entityManager->createQueryBuilder();
        $countQb->select('COUNT(g.id)')
                ->from(Game::class, 'g')
                ->leftJoin('g.gameType', 'gt');

        $whereConditions = [];
        $parameters = [];

        // Category / GameType ID filter
        if (!empty($gameTypeId)) {
            $whereConditions[] = 'g.gameType = :gameTypeId';
            $parameters['gameTypeId'] = (int) $gameTypeId;
        }

        // Search Query Filter across specific searchable database columns
        if (!empty($searchQuery)) {
            $searchQueryLower = '%' . strtolower(trim($searchQuery)) . '%';
            $col = strtolower(trim((string) $columnFilter));

            switch ($col) {
                case 'title':
                    $whereConditions[] = 'LOWER(g.title) LIKE :q';
                    break;
                case 'unique_identifier':
                case 'identifier':
                    $whereConditions[] = 'LOWER(g.uniqueIdentifier) LIKE :q';
                    break;
                case 'uuid':
                    $whereConditions[] = 'LOWER(g.uuid) LIKE :q';
                    break;
                case 'tags':
                    $whereConditions[] = 'LOWER(g.tags) LIKE :q';
                    break;
                case 'url':
                case 'game_absolute_url':
                    $whereConditions[] = 'LOWER(g.gameAbsoluteUrl) LIKE :q';
                    break;
                case 'summary':
                case 'description':
                    $whereConditions[] = '(LOWER(g.summary) LIKE :q OR LOWER(g.description) LIKE :q)';
                    break;
                case 'all':
                default:
                    $whereConditions[] = '(LOWER(g.title) LIKE :q OR LOWER(g.uniqueIdentifier) LIKE :q OR LOWER(g.uuid) LIKE :q OR LOWER(g.tags) LIKE :q OR LOWER(g.summary) LIKE :q OR LOWER(g.description) LIKE :q OR LOWER(g.gameAbsoluteUrl) LIKE :q OR LOWER(gt.name) LIKE :q)';
                    break;
            }

            $parameters['q'] = $searchQueryLower;
        }

        if (!empty($whereConditions)) {
            $compositeWhere = implode(' AND ', $whereConditions);
            $qb->where($compositeWhere);
            $countQb->where($compositeWhere);
        }

        foreach ($parameters as $key => $val) {
            $qb->setParameter($key, $val);
            $countQb->setParameter($key, $val);
        }

        try {
            $total = (int) $countQb->getQuery()->getSingleScalarResult();
        } catch (\Throwable $e) {
            $total = 0;
        }

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        try {
            $entities = $qb->getQuery()->getResult();
            $games = [];
            foreach ($entities as $g) {
                $games[] = [
                    'id'               => $g->getId(),
                    'uuid'             => $g->getUuid(),
                    'uniqueIdentifier' => $g->getUniqueIdentifier(),
                    'title'            => $g->getTitle(),
                    'summary'          => $g->getSummary(),
                    'tags'             => $g->getTags(),
                    'gameType'         => $g->getGameType() ? $g->getGameType()->getName() : 'Interactive',
                    'gameTypeId'       => $g->getGameType() ? $g->getGameType()->getId() : null,
                    'createdOn'        => $g->getCreatedOn() ? $g->getCreatedOn()->format('Y-m-d H:i') : date('Y-m-d H:i'),
                    'gameAbsoluteUrl'  => $g->getGameAbsoluteUrl(),
                    'status'           => 'Active',
                ];
            }
            return ['games' => $games, 'total' => $total];
        } catch (\Throwable $e) {
            return ['games' => [], 'total' => 0];
        }
    }

    /**
     * Helper to fetch registered games from Doctrine ORM or sample list.
     *
     * @return array
     */
    private function getGamesList(): array
    {
        if ($this->entityManager !== null) {
            try {
                $gameRepo = $this->entityManager->getRepository(Game::class);
                $entities = $gameRepo->findAll();
                if (!empty($entities)) {
                    $games = [];
                    foreach ($entities as $g) {
                        $games[] = [
                            'id'               => $g->getId(),
                            'uuid'             => $g->getUuid(),
                            'uniqueIdentifier' => $g->getUniqueIdentifier(),
                            'title'            => $g->getTitle(),
                            'summary'          => $g->getSummary(),
                            'tags'             => $g->getTags(),
                            'gameType'         => $g->getGameType() ? $g->getGameType()->getName() : 'Interactive',
                            'createdOn'        => $g->getCreatedOn() ? $g->getCreatedOn()->format('Y-m-d H:i') : date('Y-m-d H:i'),
                            'gameAbsoluteUrl'  => $g->getGameAbsoluteUrl(),
                            'status'           => 'Active',
                        ];
                    }
                    return $games;
                }
            } catch (\Throwable $e) {
                // Ignore exception
            }
        }

        return [];
    }

    /**
     * Fetch GameType options array from database.
     */
    private function getGameTypeOptions(): array
    {
        $options = [];
        if ($this->entityManager !== null) {
            try {
                $gameTypes = $this->entityManager->getRepository(GameType::class)->findAll();
                foreach ($gameTypes as $gt) {
                    $options[(string)$gt->getId()] = $gt->getName();
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $options;
    }

    /**
     * Fetch Curriculum options array from database.
     */
    private function getCurriculumOptions(): array
    {
        $options = [];
        if ($this->entityManager !== null) {
            try {
                $curriculums = $this->entityManager->getRepository(Curriculum::class)->findAll();
                foreach ($curriculums as $c) {
                    $options[(string)$c->getId()] = $c->getName();
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $options;
    }

    /**
     * Renders Toddler Assessment List / Toddler Games List management view (/game-admin/toddler-assessment).
     *
     * @return ViewModel
     */
    public function toddlerAssessmentAction(): ViewModel
    {
        $toddlerList = [];
        if ($this->entityManager !== null) {
            try {
                $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
                $entities = $repo->findAll();
                foreach ($entities as $tg) {
                    $game = $tg->getGameId();
                    $toddlerList[] = [
                        'id'           => $tg->getId(),
                        'uuid'         => $tg->getUuid(),
                        'gameId'       => $game ? $game->getId() : null,
                        'gameTitle'    => $game ? $game->getTitle() : 'Unmapped Game',
                        'gameUrl'      => $game ? $game->getGameAbsoluteUrl() : '',
                        'customConfig' => $tg->getCustomeConfig(),
                        'isActive'     => $tg->getIsActive(),
                        'createdOn'    => $tg->getCreatedOn() ? $tg->getCreatedOn()->format('Y-m-d H:i:s') : 'N/A',
                        'updatedOn'    => $tg->getUpdatedOn() ? $tg->getUpdatedOn()->format('Y-m-d H:i:s') : 'N/A',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $form = new ToddlerAssessmentForm($this->getGameOptions());

        $viewModel = new ViewModel([
            'activeNav'   => 'toddler-assessment',
            'currentUser' => $this->getActiveUser(),
            'toddlerList' => $toddlerList,
            'form'        => $form,
        ]);

        $viewModel->setTemplate('game-admin/game-admin/toddler-assessment');
        return $viewModel;
    }

    /**
     * Renders and processes the Toddler Assessment Entity Creation Form (/game-admin/toddler-assessment/create).
     *
     * @return ViewModel|\Laminas\Http\Response
     */
    public function createToddlerAssessmentAction()
    {
        $request        = $this->getRequest();
        $form           = new ToddlerAssessmentForm($this->getGameOptions());
        $successMessage = null;
        $errorMessage   = null;

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $game = null;
                    if ($this->entityManager !== null && !empty($data['gameId'])) {
                        $game = $this->entityManager->find(Game::class, (int)$data['gameId']);
                    }

                    if ($game === null) {
                        throw new \Exception('Selected Game entity could not be found.');
                    }

                    $customConfig = null;
                    if (!empty($data['customConfig'])) {
                        $customConfig = json_decode($data['customConfig'], true);
                    }

                    $isActive = isset($data['isActive']) ? (bool)$data['isActive'] : true;

                    if ($this->entityManager !== null) {
                        if ($isActive) {
                            // Deactivate all existing ToddlerGamesList entities to enforce SINGLE ACTIVE entity rule
                            $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
                            $activeEntries = $repo->findBy(['isActive' => true]);
                            foreach ($activeEntries as $item) {
                                $item->setIsActive(false);
                                $item->setUpdatedOn(new \DateTime());
                            }
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
                    }

                    $successMessage = sprintf('Toddler Assessment entry created successfully for game "%s"! (Active: %s)', $game->getTitle(), $isActive ? 'Yes' : 'No');
                    $form = new ToddlerAssessmentForm($this->getGameOptions());
                } catch (\Throwable $e) {
                    $errorMessage = 'Failed to persist Toddler Assessment entry: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'Validation failed. Please select a valid game.';
            }
        }

        $viewModel = new ViewModel([
            'activeNav'      => 'toddler-assessment',
            'currentUser'    => $this->getActiveUser(),
            'form'           => $form,
            'successMessage' => $successMessage,
            'errorMessage'   => $errorMessage,
        ]);

        $viewModel->setTemplate('game-admin/game-admin/create-toddler-assessment');
        return $viewModel;
    }

    /**
     * Activates a specific Toddler Assessment entity and deactivates all others (/game-admin/toddler-assessment/activate/:id).
     *
     * @return \Laminas\Http\Response
     */
    public function activateToddlerAssessmentAction()
    {
        $id = $this->params()->fromRoute('id');

        if (!empty($id) && $this->entityManager !== null) {
            try {
                $repo = $this->entityManager->getRepository(ToddlerGamesList::class);
                $all = $repo->findAll();
                foreach ($all as $item) {
                    $item->setIsActive(false);
                    $item->setUpdatedOn(new \DateTime());
                }

                $target = is_numeric($id) ? $repo->find((int)$id) : $repo->findOneBy(['uuid' => (string)$id]);
                if ($target) {
                    $target->setIsActive(true);
                    $target->setUpdatedOn(new \DateTime());
                }

                $this->entityManager->flush();
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $this->redirect()->toRoute('game-admin-toddler-assessment');
    }

    /**
     * Alias for toddlerAssessmentAction (/game-admin/toddler-nest).
     */
    public function toddlerNestAction(): ViewModel
    {
        return $this->toddlerAssessmentAction();
    }

    /**
     * Alias for createToddlerAssessmentAction (/game-admin/toddler-nest/create).
     */
    public function createToddlerNestAction()
    {
        return $this->createToddlerAssessmentAction();
    }

    /**
     * Fetch Game select options array for forms from database.
     */
    private function getGameOptions(): array
    {
        $options = [];
        if ($this->entityManager !== null) {
            try {
                $games = $this->entityManager->getRepository(Game::class)->findAll();
                foreach ($games as $g) {
                    $options[(string)$g->getId()] = $g->getTitle() . ' (' . $g->getUniqueIdentifier() . ')';
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $options;
    }
}
