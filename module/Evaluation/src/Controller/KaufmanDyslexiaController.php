<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class KaufmanDyslexiaController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/kaufman-dyslexia",
     *     tags={"Evaluation - KaufmanDyslexia"},
     *     description="Get a list of Kaufman Dyslexia records",
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
     *     path="/api/evaluation/kaufman-dyslexia/create",
     *     tags={"Evaluation - KaufmanDyslexia"},
     *     description="Create a new Kaufman Dyslexia record",
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
     *     path="/api/evaluation/kaufman-dyslexia/update/{id}",
     *     tags={"Evaluation - KaufmanDyslexia"},
     *     description="Update a Kaufman Dyslexia record",
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
     *     path="/api/evaluation/kaufman-dyslexia/delete/{id}",
     *     tags={"Evaluation - KaufmanDyslexia"},
     *     description="Delete a Kaufman Dyslexia record",
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
