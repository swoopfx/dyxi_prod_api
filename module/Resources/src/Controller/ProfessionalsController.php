<?php

declare(strict_types=1);

namespace Resources\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class ProfessionalsController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/resources/professionals",
     *     tags={"Resources - Professionals"},
     *     description="Get a list of Professionals",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel([
            'status' => 'success',
            'message' => 'Professionals endpoint reached',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/resources/professionals/create",
     *     tags={"Resources - Professionals"},
     *     description="Create a new Professional",
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
     *     path="/api/resources/professionals/update/{id}",
     *     tags={"Resources - Professionals"},
     *     description="Update a Professional",
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
     *     path="/api/resources/professionals/delete/{id}",
     *     tags={"Resources - Professionals"},
     *     description="Delete a Professional",
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
