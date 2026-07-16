<?php

declare(strict_types=1);

namespace Resources\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class BillingController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/resources/billing",
     *     tags={"Resources - Billing"},
     *     description="Get a list of Billings",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel([
            'status' => 'success',
            'message' => 'Billing endpoint reached',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/resources/billing/create",
     *     tags={"Resources - Billing"},
     *     description="Create a new Billing",
     *     @OA\Response(response="201", description="Created")
     * )
     */
    public function createAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Created successfully']);
    }

    /**
     * @OA\Put(
     *     path="/api/resources/billing/update/{id}",
     *     tags={"Resources - Billing"},
     *     description="Update a Billing",
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
     *     path="/api/resources/billing/delete/{id}",
     *     tags={"Resources - Billing"},
     *     description="Delete a Billing",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Deleted")
     * )
     */
    public function deleteAction()
    {
        return new JsonModel(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}
