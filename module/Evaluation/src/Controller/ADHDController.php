<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class ADHDController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/adhd",
     *     tags={"Evaluation - ADHD"},
     *     description="Get a list of ADHD records",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel(['status' => 'success', 'data' => []]);
    }

    /**
     * @OA\Post(
     *     path="/api/evaluation/adhd/create",
     *     tags={"Evaluation - ADHD"},
     *     description="Create a new ADHD record",
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
     *     path="/api/evaluation/adhd/update/{id}",
     *     tags={"Evaluation - ADHD"},
     *     description="Update an ADHD record",
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
     *     path="/api/evaluation/adhd/delete/{id}",
     *     tags={"Evaluation - ADHD"},
     *     description="Delete an ADHD record",
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
