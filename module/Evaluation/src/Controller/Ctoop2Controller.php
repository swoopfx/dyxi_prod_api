<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class Ctoop2Controller extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/ctoop2",
     *     tags={"Evaluation - Ctoop2"},
     *     description="Get a list of Ctoop2 records",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel(['status' => 'success', 'data' => []]);
    }

    /**
     * @OA\Post(
     *     path="/api/evaluation/ctoop2/create",
     *     tags={"Evaluation - Ctoop2"},
     *     description="Create a new Ctoop2 record",
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/evaluation/ctoop2/update/{id}",
     *     tags={"Evaluation - Ctoop2"},
     *     description="Update a Ctoop2 record",
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
     *     path="/api/evaluation/ctoop2/delete/{id}",
     *     tags={"Evaluation - Ctoop2"},
     *     description="Delete a Ctoop2 record",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
