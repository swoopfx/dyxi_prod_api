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
     * Displays the game catalog with Search Filtering and Pagination (/game-admin/games).
     *
     * @return ViewModel
     */
    public function gamesAction(): ViewModel
    {
        $request = $this->getRequest();
        $q       = trim((string) $this->params()->fromQuery('q', ''));
        $page    = max(1, (int) $this->params()->fromQuery('page', 1));
        $limit   = max(1, min(50, (int) $this->params()->fromQuery('limit', 10)));

        $allGames = $this->getGamesList();

        // Apply Search Filter (Filter by Title, Identifier, Tags, or Category)
        if (!empty($q)) {
            $qLower   = strtolower($q);
            $allGames = array_values(array_filter($allGames, function ($game) use ($qLower) {
                $titleMatch      = str_contains(strtolower((string)$game['title']), $qLower);
                $idMatch         = str_contains(strtolower((string)$game['uniqueIdentifier']), $qLower);
                $typeMatch       = str_contains(strtolower((string)$game['gameType']), $qLower);
                $tagsMatch       = str_contains(strtolower((string)($game['tags'] ?? '')), $qLower);
                return $titleMatch || $idMatch || $typeMatch || $tagsMatch;
            }));
        }

        $total      = count($allGames);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $limit;

        // Slice games for current pagination page
        $paginatedGames = array_slice($allGames, $offset, $limit);

        $form = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());

        $viewModel = new ViewModel([
            'activeNav'   => 'games',
            'currentUser' => $this->getActiveUser(),
            'games'       => $paginatedGames,
            'form'        => $form,
            'pagination'  => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => $total,
                'totalPages' => $totalPages,
                'q'          => $q,
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
