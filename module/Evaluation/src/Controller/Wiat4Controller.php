<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class Wiat4Controller extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/wiat4",
     *     tags={"Evaluation - Wiat4"},
     *     description="Get a list of Wiat4 records",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel(['status' => 'success', 'data' => []]);
    }

    /**
     * @OA\Post(
     *     path="/api/evaluation/wiat4/create",
     *     tags={"Evaluation - Wiat4"},
     *     description="Create a new Wiat4 record",
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/evaluation/wiat4/update/{id}",
     *     tags={"Evaluation - Wiat4"},
     *     description="Update a Wiat4 record",
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
     *     path="/api/evaluation/wiat4/delete/{id}",
     *     tags={"Evaluation - Wiat4"},
     *     description="Delete a Wiat4 record",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
