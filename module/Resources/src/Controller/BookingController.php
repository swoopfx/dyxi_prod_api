<?php

declare(strict_types=1);

namespace Resources\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class BookingController extends AbstractActionController
{
    /**
     * @OA\Get(
     *     path="/api/resources/booking",
     *     tags={"Resources - Booking"},
     *     description="Get a list of Bookings",
     * security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        return new JsonModel([
            'status' => 'success',
            'message' => 'Booking endpoint reached',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/resources/booking/create",
     *     tags={"Resources - Booking"},
     *     description="Create a new Booking",
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
     *     path="/api/resources/booking/update/{id}",
     *     tags={"Resources - Booking"},
     *     description="Update a Booking",
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
     *     path="/api/resources/booking/delete/{id}",
     *     tags={"Resources - Booking"},
     *     description="Delete a Booking",
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
