<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class DyslexiaSubtypeController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/dyslexia-subtype",
     *     tags={"Evaluation - DyslexiaSubtype"},
     *     description="Get a list of Dyslexia Subtypes",
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
     *     path="/api/evaluation/dyslexia-subtype/create",
     *     tags={"Evaluation - DyslexiaSubtype"},
     *     description="Create a new Dyslexia Subtype",
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
     *     path="/api/evaluation/dyslexia-subtype/update/{id}",
     *     tags={"Evaluation - DyslexiaSubtype"},
     *     description="Update a Dyslexia Subtype",
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
     *     path="/api/evaluation/dyslexia-subtype/delete/{id}",
     *     tags={"Evaluation - DyslexiaSubtype"},
     *     description="Delete a Dyslexia Subtype",
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
