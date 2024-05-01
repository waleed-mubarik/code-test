<?php

namespace DTApi\Service;
use DTApi\Repository\BookingRepository;

class BookingService
{
    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }


    public function distanceFeed(array $data)
    {
        $distance = getValue($data, 'distance');
        $time = getValue($data, 'time');
        $jobid = getValue($data, 'jobid');
        $session = getValue($data, 'session_time');
        $admincomment = getValue($data, 'admincomment');
        
        $flagged = $data['flagged'] === 'true' ? 'yes' : 'no';
        $manually_handled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $by_admin = $data['by_admin'] === 'true' ? 'yes' : 'no';
        
        // Check if a comment is required for flagged jobs
        if ($flagged === 'yes' && $admincomment === "") {
            return "Please, add comment";
        }
        
        // Check if the distance or time fields are set and update accordingly
        if ($time !== "" || $distance !== "") {
            $dataToUpdate = [
                'distance' => $distance,
                'time' => $time,
            ];
            $affectedRows = $this->repository->updateRelated($jobid, 'distance', $dataToUpdate);
        }
        
        // Check if any additional fields are set and update accordingly
        if ($admincomment !== "" || $session !== "" || $flagged !== "no" || $manually_handled !== "no" || $by_admin !== "no") {
            $dataToUpdate = [
                'admin_comments' => $admincomment,
                'flagged' => $flagged,
                'session_time' => $session,
                'manually_handled' => $manually_handled,
                'by_admin' => $by_admin,
            ];
            $this->repository->update($jobid, $dataToUpdate);
            return response('Record updated!');
        }
    }

    /**
     * Retrieve a value from an associative array with a default fallback.
     *
     * @param array $data The source array from which to retrieve the value.
     * @param string $key The key to look for in the array.
     * @param string $default The default value to return if the key is not found or empty.
     * @return string The retrieved value or the default value.
     */
    function getValue(array $data, string $key, string $default = ""): string {
        return isset($data[$key]) && $data[$key] !== "" ? $data[$key] : $default;
    }

    public function reopen(array $data)
    {
        $response = $this->repository->reopen($data);
    }

    public function resendNotifications(array $data)
    {
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');
        return response(['success' => 'Push sent']);
    }

        /**
     * Show a job by ID.
     *
     * @param integer $id The ID of the job to show.
     * @return mixed The job details.
     */
    public function showJob(int $id)
    {
        return $this->repository->with('translatorJobRel.user')->find($id);
    }

    /**
     * Store a new job.
     *
     * @param mixed $user The authenticated user creating the job.
     * @param array $data The data for the new job.
     * @return mixed The stored job.
     */
    public function storeJob($user, array $data)
    {
        $response = [];
        $response = [];
        
        // Validate user type
        if ($user->user_type !== env('CUSTOMER_ROLE_ID')) {
            return $this->failResponse("Translator cannot create booking");
        }

        // Early exit if 'from_language_id' is not provided
        if (!isset($data['from_language_id'])) {
            return $this->failResponse("You must fill in all fields", "from_language_id");
        }

        // Validate immediate or scheduled job
        $response['type'] = 'regular';
        if ($data['immediate'] == 'yes') {
            $data = $this->handleImmediateJob($data);
            $response['type'] = 'immediate';
        } else {
            $response = $this->validateScheduledJob($data, $response);
            if ($response['status'] === 'fail') {
                return $response;
            }
        }

        // Set default customer phone and physical types
        $data['customer_phone_type'] = isset($data['customer_phone_type']) ? 'yes' : 'no';
        $data['customer_physical_type'] = isset($data['customer_physical_type']) ? 'yes' : 'no';

        // Determine job type based on consumer type
        $data['job_type'] = $this->getJobType($user->userMeta->consumer_type);

        // Additional job parameters
        $data['b_created_at'] = now()->format('Y-m-d H:i:s');
        if (isset($data['due'])) {
            $data['will_expire_at'] = TeHelper::willExpireAt($data['due'], $data['b_created_at']);
        }
        $data['by_admin'] = $data['by_admin'] ?? 'no';

        // Create the job
        $job = $this->repository->store($user, $data);

        // Setup response
        $response['status'] = 'success';
        $response['id'] = $job->id;

        return $response;
        // Validate user type
        if ($user->user_type !== env('CUSTOMER_ROLE_ID')) {
            return $this->failResponse("Translator cannot create booking");
        }

        // Early exit if 'from_language_id' is not provided
        if (!isset($data['from_language_id'])) {
            return $this->failResponse("You must fill in all fields", "from_language_id");
        }

        // Validate immediate or scheduled job
        $response['type'] = 'regular';
        if ($data['immediate'] == 'yes') {
            $data = $this->handleImmediateJob($data);
            $response['type'] = 'immediate';
        } else {
            $response = $this->validateScheduledJob($data, $response);
            if ($response['status'] === 'fail') {
                return $response;
            }
        }

        // Set default customer phone and physical types
        $data['customer_phone_type'] = isset($data['customer_phone_type']) ? 'yes' : 'no';
        $data['customer_physical_type'] = isset($data['customer_physical_type']) ? 'yes' : 'no';

        // Determine job type based on consumer type
        $data['job_type'] = $this->getJobType($user->userMeta->consumer_type);

        // Additional job parameters
        $data['b_created_at'] = now()->format('Y-m-d H:i:s');
        if (isset($data['due'])) {
            $data['will_expire_at'] = TeHelper::willExpireAt($data['due'], $data['b_created_at']);
        }
        $data['by_admin'] = $data['by_admin'] ?? 'no';

        // Create the job
        $job = $this->repository->store($user, $data);

        // Setup response
        $response['status'] = 'success';
        $response['id'] = $job->id;

        return $response;
    }

    /**
     * Validate the scheduled job details.
     *
     * @param array $data
     * @param array $response
     * @return array
     */
    private function validateScheduledJob(array $data, array $response): array
    {
        $requiredFields = ['due_date', 'due_time', 'duration'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->failResponse("You must fill in all fields", $field);
            }
        }

        $due = $data['due_date'] . ' ' . $data['due_time'];
        $dueCarbon = Carbon::createFromFormat('m/d/Y H:i', $due);

        if ($dueCarbon->isPast()) {
            return $this->failResponse("Can't create booking in the past");
        }

        $data['due'] = $dueCarbon->format('Y-m-d H:i:s');

        return $response;
    }

    /**
     * Get the job type based on the consumer type.
     *
     * @param string $consumerType
     * @return string
     */
    private function getJobType(string $consumerType): string
    {
        switch ($consumerType) {
            case 'rwsconsumer':
                return 'rws';
            case 'ngo':
                return 'unpaid';
            case 'paid':
                return 'paid';
            default:
                return 'unknown';
        }
    }

    /**
     * Handle immediate job creation.
     *
     * @param array $data
     * @return array
     */
    private function handleImmediateJob(array $data): array
    {
        $immediateTime = 5;
        $dueCarbon = Carbon::now()->addMinute($immediateTime);
        $data['due'] = $dueCarbon->format('Y-m-d H:i:s');
        $data['immediate'] = 'yes';
        $data['customer_phone_type'] = 'yes';

        return $data;
    }

    /**
     * Generate a response for a failed validation.
     *
     * @param string $message
     * @param string|null $fieldName
     * @return array
     */
    private function failResponse(string $message, string $fieldName = null): array
    {
        $response = [
            'status' => 'fail',
            'message' => $message,
        ];

        if ($fieldName) {
            $response['field_name'] = $fieldName;
        }

        return $response;
    }

    /**
     * Update an existing job by ID.
     *
     * @param integer $id The ID of the job to update.
     * @param array $data The updated data.
     * @param mixed $user The authenticated user.
     * @return mixed The updated job.
     */
    public function updateJob(int $id, array $data, $user)
    {
        return $this->repository->updateJob($id, $data, $user);
    }

    /**
     * Store an immediate job email.
     *
     * @param array $data The data for the email.
     * @return mixed The response after storing.
     */
    public function storeImmediateJobEmail(array $data)
    {
        return $this->repository->storeJobEmail($data);
    }

    /**
     * Get job history for a specific user.
     *
     * @param integer $userId The user ID.
     * @param mixed $request The HTTP request.
     * @return mixed The job history.
     */
    public function getJobHistory(int $userId, $request)
    {
        return $this->repository->getUsersJobsHistory($userId, $request);
    }

    /**
     * Accept a job.
     *
     * @param array $data The job data to accept.
     * @param mixed $user The authenticated user.
     * @return mixed The response after accepting.
     */
    public function acceptJob(array $data, $user)
    {
        return $this->repository->acceptJob($data, $user);
    }

    /**
     * Cancel a job.
     *
     * @param array $data The job data to cancel.
     * @param mixed $user The authenticated user.
     * @return mixed The response after canceling.
     */
    public function cancelJob(array $data, $user)
    {
        return $this->repository->cancelJobAjax($data, $user);
    }

    /**
     * End a job.
     *
     * @param array $data The job data to end.
     * @return mixed The response after ending the job.
     */
    public function endJob(array $data)
    {
        return $this->repository->endJob($data);
    }

    /**
     * Handle customer not calling.
     *
     * @param array $data The related data.
     * @return mixed The response after handling.
     */
    public function customerNotCall(array $data)
    {
        return $this->repository->customerNotCall($data);
    }

    /**
     * Get potential jobs for a specific user.
     *
     * @param mixed $user The authenticated user.
     * @return mixed The potential jobs.
     */
    public function getPotentialJobs($user)
    {
        return $this->repository->getPotentialJobs($user);
    }
}
