<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 *
 * Provides a standardized way to format API responses.
 */
trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param  mixed  $data
     */
    protected function successResponse(
        $data = null,
        string $message = 'Resource retrieved successfully',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'code' => $statusCode,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(
        string $message = 'Error occurred',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'code' => $statusCode,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function validationErrorResponse(
        array|MessageBag $errors,
        string $message = 'Validation failed',
        int $statusCode = 422
    ): JsonResponse {
        if ($errors instanceof MessageBag) {
            $errors = $errors->toArray();
        }

        return response()->json([
            'success' => false,
            'code' => $statusCode,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Return a standardized paginated response.
     *
     * @param  LengthAwarePaginator  $paginator
     * @param  array  $extraMeta  (sorting, filters, etc.)
     */
    protected function paginatedResponse(
        $paginator,
        string $message = 'List Data',
        array $extraMeta = [],
        int $statusCode = 200
    ): JsonResponse {
        // Build links for pagination (first, last, prev, next)
        $links = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        // Build page links (for numbered pagination)
        $pageLinks = [];
        // Limit page links if too many pages? For now follow user request exactly.
        for ($i = 1; $i <= $paginator->lastPage(); $i++) {
            $pageLinks[] = [
                'url' => $paginator->url($i),
                'label' => $i,
                'active' => $i === $paginator->currentPage(),
            ];
        }

        $response = [
            'success' => true,
            'code' => $statusCode,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'has_previous_pages' => $paginator->currentPage() > 1,
            ],
            'links' => $links,
            'page_links' => $pageLinks,
        ];

        // Merge extra meta (sorting, filters) if provided
        if (! empty($extraMeta)) {
            $response = array_merge($response, $extraMeta);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response.
     *
     * @param  mixed  $data
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a too many requests response (Rate Limit).
     */
    protected function tooManyRequestsResponse(string $message = 'Too Many Requests', int $retryAfterSeconds = 60): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 429,
            'message' => $message,
            'retry_after' => $retryAfterSeconds,
            'retry_after_human' => gmdate('i:s', $retryAfterSeconds).' minutes',
        ], 429)->header('Retry-After', $retryAfterSeconds);
    }
}