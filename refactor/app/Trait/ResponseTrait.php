<?php

namespace App\Trait;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    /**
     * Return a success response.
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    public function successResponse($data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    public function errorResponse(string $message, int $status = 500): JsonResponse
    {
        return response()->json(['success' => false, 'error' => $message], $status);
    }
}
