<?php

namespace Consultant\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Consultant\Service\ConsultantService;
use Consultant\Entity\Consultant;
use Consultant\Entity\ConsultantCategory;
use Consultant\Entity\ConsultantBooking;

/**
 * @OA\Tag(
 *     name="Consultant",
 *     description="API endpoints for creating, listing with filters, viewing, and booking consultant appointments."
 * )
 */
class ConsultantController extends AbstractActionController
{
    /**
     * @var ConsultantService
     */
    private $consultantService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    public function __construct(ConsultantService $consultantService, ApiAuthenticateService $apiAuthService)
    {
        $this->consultantService = $consultantService;
        $this->apiAuthService = $apiAuthService;
    }

    /**
     * Creates a new Consultant profile.
     *
     * @OA\Post(
     *     path="/api/consultant/create",
     *     tags={"Consultant"},
     *     summary="Create a new Consultant profile",
     *     description="Creates a new professional consultant, doctor, or psychologist profile on the platform.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to create a consultant. Required fields: 'fullname' and 'email'. Category can be passed as category_id, category_uuid, or category name.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"fullname", "email"},
     *                     description="Consultant Creation Schema",
     *                     @OA\Property(property="fullname", type="string", example="Dr. Sarah Connor", description="[REQUIRED] Full name of the consultant."),
     *                     @OA\Property(property="email", type="string", format="email", example="sarah.connor@example.com", description="[REQUIRED] Unique email address for notifications."),
     *                     @OA\Property(property="title", type="string", example="Dr.", description="[OPTIONAL] Professional title (e.g., 'Dr.', 'Prof.', 'Mr.', 'Mrs.', 'Ms.')."),
     *                     @OA\Property(property="introduction", type="string", example="Experienced Child & Adolescent Psychologist.", description="[OPTIONAL] Short introductory summary."),
     *                     @OA\Property(property="description", type="string", example="Specializing in neurodevelopmental evaluations for ADHD, Dyslexia, and Dyscalculia.", description="[OPTIONAL] Detailed bio and qualifications."),
     *                     @OA\Property(property="category", type="string", example="Psychologist", description="[OPTIONAL] Category name ('Doctor', 'Consultant', 'Psychologist'), category ID (integer), or category UUID."),
     *                     @OA\Property(property="category_id", type="integer", example=1, description="[OPTIONAL] ConsultantCategory ID."),
     *                     @OA\Property(property="category_uuid", type="string", example="cat-uuid-1234", description="[OPTIONAL] ConsultantCategory UUID."),
     *                     @OA\Property(property="speciality", type="string", example="Child Psychology & ADHD", description="[OPTIONAL] Primary field of specialty."),
     *                     @OA\Property(property="phone", type="string", example="+1234567890", description="[OPTIONAL] Direct phone number."),
     *                     @OA\Property(property="user_id", type="string", example="1", description="[OPTIONAL] Linked User account integer ID or UUID.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Consultant profile created successfully",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="a2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d"),
     *                         @OA\Property(property="fullname", type="string", example="Dr. Sarah Connor"),
     *                         @OA\Property(property="title", type="string", example="Dr."),
     *                         @OA\Property(property="introduction", type="string", example="Experienced Child & Adolescent Psychologist."),
     *                         @OA\Property(property="description", type="string", example="Specializing in neurodevelopmental evaluations..."),
     *                         @OA\Property(
     *                             property="category",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=3),
     *                             @OA\Property(property="uuid", type="string", example="c1d2e3f4-5678-90ab-cdef-1234567890ab"),
     *                             @OA\Property(property="name", type="string", example="Psychologist"),
     *                             @OA\Property(property="description", type="string", example="Licensed psychologist specializing in mental health...")
     *                         ),
     *                         @OA\Property(property="category_name", type="string", example="Psychologist"),
     *                         @OA\Property(property="speciality", type="string", example="Child Psychology & ADHD"),
     *                         @OA\Property(property="email", type="string", example="sarah.connor@example.com"),
     *                         @OA\Property(property="phone", type="string", example="+1234567890"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 15:00:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 15:00:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Successfully created Consultant profile.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request - Validation or duplicate email error",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ConsultantCreationError"),
     *                     @OA\Property(property="description", type="string", example="Consultant email is required.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function createAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use POST."
            ]);
            return $jsonModel;
        }

        try {
            $json = $request->getContent();
            $postData = (array) json_decode($json, true);
            if (empty($postData)) {
                $postData = $request->getPost()->toArray();
            }

            $consultant = $this->consultantService->createConsultant($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($consultant),
                "description" => "Successfully created Consultant profile."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantCreationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Alias for createAction.
     */
    public function registerAction()
    {
        return $this->createAction();
    }

    /**
     * Lists Consultant Categories.
     *
     * @OA\Get(
     *     path="/api/consultant/categories",
     *     tags={"Consultant"},
     *     summary="List all Consultant Categories",
     *     description="Retrieve all available consultant categories (e.g. Doctor, Consultant, Psychologist).",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="uuid", type="string", example="c1d2e3f4-5678-90ab-cdef-1234567890ab"),
     *                             @OA\Property(property="name", type="string", example="Doctor"),
     *                             @OA\Property(property="description", type="string", example="Medical doctor specializing in clinical assessment...")
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function categoriesAction()
    {
        $jsonModel = new JsonModel();
        $categories = $this->consultantService->listCategories();

        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                "id" => $cat->getId(),
                "uuid" => $cat->getUuid(),
                "name" => $cat->getName(),
                "description" => $cat->getDescription()
            ];
        }

        $jsonModel->setVariables([
            "success" => true,
            "data" => $data
        ]);

        return $jsonModel;
    }

    /**
     * Lists Consultants with searchable filters.
     *
     * @OA\Get(
     *     path="/api/consultant/list",
     *     tags={"Consultant"},
     *     summary="List Consultants with searchable filters",
     *     description="Retrieve list of consultants filtered by category (ID, UUID, or name), speciality, title, or search keyword.",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID, UUID, or category name ('Doctor', 'Consultant', 'Psychologist').",
     *         @OA\Schema(type="string", example="Doctor")
     *     ),
     *     @OA\Parameter(
     *         name="speciality",
     *         in="query",
     *         required=false,
     *         description="Filter by speciality or specialization keyword.",
     *         @OA\Schema(type="string", example="ADHD")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         required=false,
     *         description="Filter by title (e.g. 'Dr.').",
     *         @OA\Schema(type="string", example="Dr.")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Free text search query across fullname, title, category name, speciality, introduction, and description.",
     *         @OA\Schema(type="string", example="Sarah")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="uuid", type="string", example="a2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d"),
     *                             @OA\Property(property="fullname", type="string", example="Dr. Sarah Connor"),
     *                             @OA\Property(property="title", type="string", example="Dr."),
     *                             @OA\Property(property="introduction", type="string", example="Experienced Child Psychologist."),
     *                             @OA\Property(property="description", type="string", example="Specialized in ADHD assessments."),
     *                             @OA\Property(
     *                                 property="category",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=3),
     *                                 @OA\Property(property="uuid", type="string", example="c1d2e3f4-5678-90ab-cdef-1234567890ab"),
     *                                 @OA\Property(property="name", type="string", example="Psychologist"),
     *                                 @OA\Property(property="description", type="string", example="Licensed psychologist...")
     *                             ),
     *                             @OA\Property(property="category_name", type="string", example="Psychologist"),
     *                             @OA\Property(property="speciality", type="string", example="Child Psychology & ADHD"),
     *                             @OA\Property(property="email", type="string", example="sarah.connor@example.com"),
     *                             @OA\Property(property="phone", type="string", example="+1234567890"),
     *                             @OA\Property(property="status", type="string", example="active")
     *                         )
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function listAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $params = $request->getQuery()->toArray();

            $consultants = $this->consultantService->listConsultants($params);
            $data = [];
            foreach ($consultants as $consultant) {
                $data[] = $this->mapEntityToArray($consultant);
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $data
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * View details of a specific Consultant.
     *
     * @OA\Get(
     *     path="/api/consultant/view/{id}",
     *     tags={"Consultant"},
     *     summary="View Consultant details",
     *     description="Retrieve details of a single consultant profile by integer ID or UUID string.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="[REQUIRED] Integer ID or UUID string of the consultant.",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="a2b3c4d5-e6f7-8a9b-0c1d-2e3f4a5b6c7d"),
     *                         @OA\Property(property="fullname", type="string", example="Dr. Sarah Connor"),
     *                         @OA\Property(property="title", type="string", example="Dr."),
     *                         @OA\Property(property="introduction", type="string", example="Experienced Child Psychologist."),
     *                         @OA\Property(property="description", type="string", example="Specialized in ADHD assessments."),
     *                         @OA\Property(
     *                             property="category",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=3),
     *                             @OA\Property(property="uuid", type="string", example="c1d2e3f4-5678-90ab-cdef-1234567890ab"),
     *                             @OA\Property(property="name", type="string", example="Psychologist"),
     *                             @OA\Property(property="description", type="string", example="Licensed psychologist...")
     *                         ),
     *                         @OA\Property(property="category_name", type="string", example="Psychologist"),
     *                         @OA\Property(property="speciality", type="string", example="Child Psychology & ADHD"),
     *                         @OA\Property(property="email", type="string", example="sarah.connor@example.com"),
     *                         @OA\Property(property="phone", type="string", example="+1234567890"),
     *                         @OA\Property(property="status", type="string", example="active")
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="400", description="Bad Request - Consultant not found"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function viewAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $id = $this->params()->fromRoute('id');
            if (empty($id)) {
                $id = $this->params()->fromQuery('id') ?? $this->params()->fromQuery('uuid');
            }

            if (empty($id)) {
                throw new \Exception("Consultant identifier (id or uuid) is required.");
            }

            $consultant = $this->consultantService->getConsultantInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($consultant)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantViewError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    /**
     * Book a Consultant appointment.
     *
     * @OA\Post(
     *     path="/api/consultant/book",
     *     tags={"Consultant"},
     *     summary="Book a Consultant appointment",
     *     description="Books an appointment with a consultant, creates a booking record with status 'initiated', and dispatches email notifications to both the consultant and the booking parent.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to book an appointment. Required fields: 'consultant_id', 'appointment_date', 'preferred_time'.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     required={"consultant_id", "appointment_date", "preferred_time"},
     *                     description="Consultant Booking Schema",
     *                     @OA\Property(property="consultant_id", type="string", example="1", description="[REQUIRED] Integer ID or UUID string of the selected consultant."),
     *                     @OA\Property(property="appointment_date", type="string", format="date", example="2026-10-15", description="[REQUIRED] Appointment date in YYYY-MM-DD format."),
     *                     @OA\Property(property="preferred_time", type="string", example="10:30 AM", description="[REQUIRED] Preferred appointment time (e.g. '10:00 AM', '02:30 PM')."),
     *                     @OA\Property(property="reason_for_visit", type="string", example="Initial consultation for ADHD assessment", description="[OPTIONAL] Reason for visit or notes."),
     *                     @OA\Property(property="user_id", type="string", example="5", description="[OPTIONAL] Parent User integer ID or UUID (inferred from bearer token identity if not passed).")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Consultant appointment booked successfully. Emails dispatched.",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="uuid", type="string", example="f1e2d3c4-b5a6-7890-1234-56789abcdef0"),
     *                         @OA\Property(
     *                             property="consultant",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="fullname", type="string", example="Dr. Sarah Connor"),
     *                             @OA\Property(property="email", type="string", example="sarah.connor@example.com")
     *                         ),
     *                         @OA\Property(
     *                             property="parent_user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=5),
     *                             @OA\Property(property="fullname", type="string", example="Jane Doe"),
     *                             @OA\Property(property="email", type="string", example="jane.doe@example.com")
     *                         ),
     *                         @OA\Property(property="appointment_date", type="string", example="2026-10-15"),
     *                         @OA\Property(property="preferred_time", type="string", example="10:30 AM"),
     *                         @OA\Property(property="reason_for_visit", type="string", example="Initial consultation for ADHD assessment"),
     *                         @OA\Property(property="status", type="string", example="initiated", enum={"initiated", "confirmed", "executed", "cancelled"}),
     *                         @OA\Property(property="created_on", type="string", example="2026-09-09 15:15:00"),
     *                         @OA\Property(property="updated_on", type="string", example="2026-09-09 15:15:00")
     *                     ),
     *                     @OA\Property(property="description", type="string", example="Consultant appointment booked successfully. Email notifications have been sent.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request - Missing or invalid booking data",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="ConsultantBookingError"),
     *                     @OA\Property(property="description", type="string", example="Appointment date is required.")
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="405", description="Method Not Allowed")
     * )
     */
    public function bookAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use POST."
            ]);
            return $jsonModel;
        }

        try {
            $parentUser = null;
            $identity = $this->apiAuthService->getContainerIdentity();
            if ($identity instanceof \Authentication\Entity\User) {
                $parentUser = $identity;
            }

            $json = $request->getContent();
            $postData = (array) json_decode($json, true);
            if (empty($postData)) {
                $postData = $request->getPost()->toArray();
            }

            $booking = $this->consultantService->bookConsultant($postData, $parentUser);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapBookingToArray($booking),
                "description" => "Consultant appointment booked successfully. Email notifications have been sent."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantBookingError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    private function mapEntityToArray(Consultant $consultant): array
    {
        $category = $consultant->getCategory();
        return [
            "id" => $consultant->getId(),
            "uuid" => $consultant->getUuid(),
            "fullname" => $consultant->getFullname(),
            "title" => $consultant->getTitle(),
            "introduction" => $consultant->getIntroduction(),
            "description" => $consultant->getDescription(),
            "category" => $category ? [
                "id" => $category->getId(),
                "uuid" => $category->getUuid(),
                "name" => $category->getName(),
                "description" => $category->getDescription()
            ] : null,
            "category_name" => $category ? $category->getName() : null,
            "speciality" => $consultant->getSpeciality(),
            "specialization" => $consultant->getSpecialization(),
            "bio" => $consultant->getBio(),
            "email" => $consultant->getEmail(),
            "phone" => $consultant->getPhone(),
            "status" => $consultant->getStatus(),
            "user" => $consultant->getUser() ? [
                "id" => $consultant->getUser()->getId(),
                "uuid" => $consultant->getUser()->getUuid(),
                "fullname" => $consultant->getUser()->getFullname(),
                "email" => $consultant->getUser()->getEmail()
            ] : null,
            "created_on" => $consultant->getCreatedOn() ? $consultant->getCreatedOn()->format('Y-m-d H:i:s') : null,
            "updated_on" => $consultant->getUpdatedOn() ? $consultant->getUpdatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }

    private function mapBookingToArray(ConsultantBooking $booking): array
    {
        return [
            "id" => $booking->getId(),
            "uuid" => $booking->getUuid(),
            "consultant" => $this->mapEntityToArray($booking->getConsultant()),
            "parent_user" => $booking->getUser() ? [
                "id" => $booking->getUser()->getId(),
                "uuid" => $booking->getUser()->getUuid(),
                "fullname" => $booking->getUser()->getFullname(),
                "email" => $booking->getUser()->getEmail()
            ] : null,
            "appointment_date" => $booking->getAppointmentDate() ? $booking->getAppointmentDate()->format('Y-m-d') : null,
            "preferred_time" => $booking->getPreferredTime(),
            "reason_for_visit" => $booking->getReasonForVisit(),
            "status" => $booking->getStatus(),
            "created_on" => $booking->getCreatedOn() ? $booking->getCreatedOn()->format('Y-m-d H:i:s') : null,
            "updated_on" => $booking->getUpdatedOn() ? $booking->getUpdatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}
