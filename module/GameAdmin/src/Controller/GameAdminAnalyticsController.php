<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminAnalyticsController.php
 * ============================================================================
 * Description: Controller responsible for rendering telemetry metrics, session
 * performance analytics, and handling real-time polling JSON API endpoints.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller;

use Doctrine\ORM\EntityManager;
use Game\Entity\Game;
use Game\Service\CurriculumService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

/**
 * Controller class for telemetry analytics and stats polling endpoints.
 */
class GameAdminAnalyticsController extends AbstractActionController
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
     * GameAdminAnalyticsController Constructor.
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
     * Renders Telemetry and Performance Analytics View (/game-admin/analytics).
     *
     * @return ViewModel
     */
    public function analyticsAction(): ViewModel
    {
        $totalGames = 0;
        $totalCurriculums = 0;
        $activeToddlerAssessmentTitle = 'None';
        $toddlerActive = null;

        if ($this->entityManager !== null) {
            try {
                $totalGames = count($this->entityManager->getRepository(\Game\Entity\Game::class)->findAll());
                $totalCurriculums = count($this->entityManager->getRepository(\Game\Entity\Curriculum::class)->findAll());
            } catch (\Throwable $e) {}
        }

        if ($this->curriculumService) {
            try {
                $toddlerActive = $this->curriculumService->getActiveToddlerAssessment();
                if ($toddlerActive && $toddlerActive->getGameId() instanceof \Game\Entity\Game) {
                    $activeToddlerAssessmentTitle = $toddlerActive->getGameId()->getTitle();
                }
            } catch (\Throwable $e) {}
        }

        $globalActivePlayers = $this->curriculumService ? $this->curriculumService->getActiveConcurrentPlayers('global') : 0;
        $toddlerActivePlayers = $this->curriculumService ? $this->curriculumService->getActiveConcurrentPlayers('toddler_assessment') : 0;

        $isReset = $this->params()->fromQuery('reset') === '1';

        $viewModel = new ViewModel([
            'activeNav'                     => 'analytics',
            'currentUser'                   => $this->getActiveUser(),
            'isReset'                       => $isReset,
            'metrics'                       => [
                'global_active_players'     => $globalActivePlayers,
                'toddler_active_players'    => $toddlerActivePlayers,
                'total_games'               => $totalGames,
                'total_curriculums'         => $totalCurriculums,
                'active_toddler_assessment' => $activeToddlerAssessmentTitle,
                'cache_namespace'           => \Game\Service\CurriculumService::ANALYTICS_CACHE_NAMESPACE,
            ],
        ]);

        $viewModel->setTemplate('game-admin/game-admin/analytics');
        return $viewModel;
    }

    /**
     * Action to reset/flush the Redis analytics cache namespace without affecting other namespaces (/game-admin/analytics/reset-cache).
     */
    public function resetCacheAction()
    {
        if ($this->curriculumService) {
            $this->curriculumService->clearAnalyticsCache();
        }
        return $this->redirect()->toRoute('game-admin-analytics', [], ['query' => ['reset' => '1']]);
    }

    /**
     * API endpoint to programmatically reset the Redis analytics cache namespace (/api/game-admin/analytics/reset).
     *
     * @return JsonModel
     */
    public function resetCacheApiAction(): JsonModel
    {
        $success = false;
        if ($this->curriculumService) {
            $success = $this->curriculumService->clearAnalyticsCache();
        }

        return new JsonModel([
            'success'     => $success,
            'namespace'   => \Game\Service\CurriculumService::ANALYTICS_CACHE_NAMESPACE,
            'description' => 'Successfully cleared and reset analytics Redis cache namespace without affecting other namespaces.',
            'timestamp'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * API endpoint returning live system stats for AJAX dashboard polling (/api/game-admin/stats).
     *
     * @return JsonModel
     */
    public function statsApiAction(): JsonModel
    {
        $totalGames = 0;
        $totalPlays = 0;
        if ($this->entityManager !== null) {
            try {
                $games = $this->entityManager->getRepository(\Game\Entity\Game::class)->findAll();
                $totalGames = count($games);
            } catch (\Throwable $e) {
                // Ignore exception
            }
        }

        $globalActivePlayers = $this->curriculumService ? $this->curriculumService->getActiveConcurrentPlayers('global') : 0;
        $toddlerActivePlayers = $this->curriculumService ? $this->curriculumService->getActiveConcurrentPlayers('toddler_assessment') : 0;

        return new JsonModel([
            'success'   => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics'   => [
                'active_players'         => $globalActivePlayers,
                'global_active_players'  => $globalActivePlayers,
                'toddler_active_players' => $toddlerActivePlayers,
                'total_games'            => $totalGames,
                'cache_namespace'        => \Game\Service\CurriculumService::ANALYTICS_CACHE_NAMESPACE,
            ],
        ]);
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
}
