<?php

declare(strict_types=1);

namespace Resources\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class SettingsController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/resources/settings",
     *     tags={"Resources - Settings"},
     *     description="Get Settings",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel([
            'status' => 'success',
            'message' => 'Settings endpoint reached',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/resources/settings/create",
     *     tags={"Resources - Settings"},
     *     description="Create a new Setting",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/resources/settings/update/{id}",
     *     tags={"Resources - Settings"},
     *     description="Update a Setting",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Updated")
     * )
     */
    public function updateAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Updated successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/resources/settings/delete/{id}",
     *     tags={"Resources - Settings"},
     *     description="Delete a Setting",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
