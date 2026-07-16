<?php

declare(strict_types=1);

namespace Evaluation\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class NeuroDiversityScaleController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/evaluation/neuro-diversity-scale",
     *     tags={"Evaluation - NeuroDiversityScale"},
     *     description="Get a list of NeuroDiversityScale records",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel(['status' => 'success', 'data' => []]);
    }

    /**
     * @OA\Post(
     *     path="/api/evaluation/neuro-diversity-scale/create",
     *     tags={"Evaluation - NeuroDiversityScale"},
     *     description="Create a new NeuroDiversityScale record",
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/evaluation/neuro-diversity-scale/update/{id}",
     *     tags={"Evaluation - NeuroDiversityScale"},
     *     description="Update a NeuroDiversityScale record",
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
     *     path="/api/evaluation/neuro-diversity-scale/delete/{id}",
     *     tags={"Evaluation - NeuroDiversityScale"},
     *     description="Delete a NeuroDiversityScale record",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
