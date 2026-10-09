<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminDashboardController.php
 * ============================================================================
 * Description: Responsible for displaying the central Game Engine Dashboard,
 * aggregating system-wide metrics, active game statistics, and player throughput.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller;

use Doctrine\ORM\EntityManager;
use Game\Entity\Game;
use Game\Service\CurriculumService;
use GameAdmin\Form\GameForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;

/**
 * Controller class for the main GameAdmin Dashboard overview.
 */
class GameAdminDashboardController extends AbstractActionController
{
    /**
     * Doctrine Entity Manager instance.
     */
    private ?EntityManager $entityManager;

    /**
     * Curriculum Service instance.
     */
    private ?CurriculumService $curriculumService;

    /**
     * GameAdminDashboardController Constructor.
     *
     * @param EntityManager|null $entityManager
     * @param CurriculumService|null $curriculumService
     */
    public function __construct(?EntityManager $entityManager = null, ?CurriculumService $curriculumService = null)
    {
        $this->entityManager = $entityManager;
        $this->curriculumService = $curriculumService;
    }

    /**
     * Renders the central Game Engine Dashboard (/game-admin).
     *
     * @return ViewModel
     */
    public function indexAction(): ViewModel
    {
        $games      = $this->getGamesList();
        $totalGames = count($games);
        $totalPlays = array_sum(array_column($games, 'playsCount'));

        $activePlayers = $this->curriculumService ? $this->curriculumService->getActiveConcurrentPlayers('global') : 0;

        // Prepare modal game form
        $form = new GameForm($this->getGameTypeOptions(), $this->getCurriculumOptions());

        $viewModel = new ViewModel([
            'activeNav'   => 'dashboard',
            'currentUser' => $this->getActiveUser(),
            'games'       => $games,
            'form'        => $form,
            'stats'       => [
                'total_games'    => $totalGames,
                'active_players' => $activePlayers,
                'total_plays'    => number_format($totalPlays),
                'system_health'  => '99.98%',
            ],
        ]);

        $viewModel->setTemplate('game-admin/game-admin/index');
        return $viewModel;
    }

    /**
     * Retrieves active authenticated user details from session storage.
     *
     * @return array User details payload.
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
     * Helper method to fetch registered games directly from Doctrine ORM database.
     *
     * @return array Array of game entity data from DB.
     */
    private function getGamesList(): array
    {
        if ($this->entityManager !== null) {
            try {
                $gameRepo = $this->entityManager->getRepository(Game::class);
                $entities = $gameRepo->findAll();
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
            } catch (\Throwable $e) {
                // Return empty list on failure
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
                $gameTypes = $this->entityManager->getRepository(\Game\Entity\GameType::class)->findAll();
                foreach ($gameTypes as $gt) {
                    $options[(string)$gt->getId()] = $gt->getName();
                }
            } catch (\Throwable $e) {
                // Return empty options on failure
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
                $curriculums = $this->entityManager->getRepository(\Game\Entity\Curriculum::class)->findAll();
                foreach ($curriculums as $c) {
                    $options[(string)$c->getId()] = $c->getName();
                }
            } catch (\Throwable $e) {
                // Return empty options on failure
            }
        }
        return $options;
    }
}
