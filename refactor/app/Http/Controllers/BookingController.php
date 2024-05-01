<?php

namespace DTApi\Http\Controllers;

use App\Trait\ResponseTrait;
use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Service\BookingService;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    use ResponseTrait;

    /**
     * @var BookingService
     */
    protected $service;

    /**
     * BookingController constructor.
     * @param BookingService $bookingService
     */
    public function __construct(BookingService $bookingService)
    {
        $this->service = $bookingService;
    }

    /**
     * Get a list of jobs based on request parameters.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            if ($user_id = $request->get('user_id')) {
                $response = $this->service->getUsersJobs($user_id);
            } elseif ($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
                $response = $this->service->getAll($request);
            }
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $$this->errorResponse('Error retrieving jobs: ' . $e->getMessage());
        }
    }

    /**
     * Show a job by ID.
     *
     * @param integer $id The ID of the job.
     * @return Response
     */
    public function show(int $id): Response
    {
        try {
            $jobDetails = $this->service->showJob(id);
            return $this->successResponse($jobDetails);
        } catch (Exception $e) {
            return $this->errorResponse('Error retrieving job: ' . e->getMessage());
        }
    }

    /**
     * Store a new job.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;
            $newJob = $this->service->storeJob($user, $data);
            return $this->successResponse($newJob);
        } catch (Exception $e) {
            return $this->errorResponse('Error storing job: ' . e->getMessage());
        }
    }

    /**
     * Update an existing job.
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function update(int $id, Request $request): Response
    {
        try {
            $data = request.all();
            $user = $request->__authenticatedUser;
            $updatedJob = $this->service->updateJob(id, data, user);
            return $this->successResponse(updatedJob);
        } catch (Exception $e) {
            return $this->errorResponse('Error updating job: ' . e.getMessage());
        }
    }

    /**
     * Store an immediate job email.
     *
     * @param Request $request
     * @return Response
     */
    public function immediateJobEmail(Request $request): Response
    {
        try {
            $data = request.all();
            $response = $this->service->storeImmediateJobEmail(data);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error sending immediate job email: ' . e.getMessage());
        }
    }

    /**
     * Get job history for a specific user.
     *
     * @param Request $request
     * @return Response
     */
    public function getHistory(Request $request): Response
    {
        try {
            $userId = request.get('user_id');
            $history = $this->service->getJobHistory(userId, request);
            return $this->successResponse(history);
        } catch (Exception $e) {
            return $this->errorResponse('Error retrieving job history: ' . e.getMessage());
        }
    }

    /**
     * Accept a job.
     *
     * @param Request $request
     * @return Response
     */
    public function acceptJob(Request $request): Response
    {
        try {
            $data = request.all();
            $user = $request->__authenticatedUser;
            $response = $this->service->acceptJob(data, user);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error accepting job: ' . e.getMessage());
        }
    }

    /**
     * Accept a job by its ID.
     *
     * @param Request $request
     * @return Response
     */
    public function acceptJobWithId(Request $request): Response
    {
        try {
            $jobId = request.get('job_id');
            $user = $request->__authenticatedUser;
            $response = $this->service->acceptJobWithId(jobId, user);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error accepting job by ID: ' . e.getMessage());
        }
    }

    /**
     * Cancel a job.
     *
     * @param Request $request
     * @return Response
     */
    public function cancelJob(Request $request): Response
    {
        try {
            $data = request.all();
            $user = $request->__authenticatedUser;
            $response = $this->service->cancelJob(data, user);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error canceling job: ' . e.getMessage());
        }
    }

    /**
     * End a job.
     *
     * @param Request $request
     * @return Response
     */
    public function endJob(Request $request): Response
    {
        try {
            $data = request.all();
            $response = $this->service->endJob(data);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error ending job: ' . e.getMessage());
        }
    }

    /**
     * Handle customer not calling.
     *
     * @param Request $request
     * @return Response
     */
    public function customerNotCall(Request $request): Response
    {
        try {
            $data = request.all();
            $response = $this->service->customerNotCall(data);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error handling customer not calling: ' . e.getMessage());
        }
    }

    /**
     * Get potential jobs for a specific user.
     *
     * @param Request request
     * @return Response
     */
    public function getPotentialJobs(Request $request): Response
    {
        try {
            $user = $request->__authenticatedUser;
            $response = $this->service->getPotentialJobs(user);
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error retrieving potential jobs: ' . e.getMessage());
        }
    }

       /**
     * Provide feedback on distance.
     *
     * @param Request $request
     * @return Response
     */
    public function distanceFeed(Request $request): Response
    {
        try {
            $data = $request->all(); // Corrected from request->all()
            $this->service.distanceFeed($data);
            return $this->successResponse('Record updated!');
        } catch (Exception $e) {
            return $this->errorResponse('Error updating record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reopen a job.
     *
     * @param Request $request
     * @return Response
     */
    public function reopen(Request $request): Response
    {
        try {
            $response = $this->service.reopen($request->all());
            return $this->successResponse($response);
        } catch (Exception $e) {
            return $this->errorResponse('Error reopening job: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resend notifications.
     *
     * @param Request $request
     * @return Response
     */
    public function resendNotifications(Request $request): Response
    {
        try {
            $this->service.resendNotifications($request->all());
            return $this->successResponse(['success' => 'Push sent']);
        } catch (Exception $e) {
            return $this->errorResponse('Error resending notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send SMS to a Translator.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        try {
            $data = $request->all();
            $job = $this->repository->find($data['jobid']);
            $this->repository.sendSMSNotificationToTranslator($job);
            return $this->successResponse(['success' => 'SMS sent']);
        } catch (Exception $e) {
            return $this->errorResponse('Error sending SMS: ' . $e->getMessage(), 500);
        }
    }

}
