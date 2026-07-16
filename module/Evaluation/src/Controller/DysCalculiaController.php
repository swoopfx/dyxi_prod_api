<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class DysCalculiaController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/dyscalculia",
     *     tags={"Evaluation - DysCalculia"},
     *     description="Get a list of DysCalculia records",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel(['status' => 'success', 'data' => []]);
    }

    /**
     * @OA\Post(
     *     path="/api/evaluation/dyscalculia/create",
     *     tags={"Evaluation - DysCalculia"},
     *     description="Create a new DysCalculia record",
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/evaluation/dyscalculia/update/{id}",
     *     tags={"Evaluation - DysCalculia"},
     *     description="Update a DysCalculia record",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Updated")
     * )
     */
    public function updateAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Updated successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/evaluation/dyscalculia/delete/{id}",
     *     tags={"Evaluation - DysCalculia"},
     *     description="Delete a DysCalculia record",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
