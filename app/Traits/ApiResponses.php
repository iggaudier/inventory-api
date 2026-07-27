<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Response;

trait ApiResponses
{
    /**
     * 200 OK
     */
    protected function ok(string $message = 'Success', mixed $data = [], array $meta = []): JsonResponse
    {
        return $this->success($message, $data, 200, $meta);
    }

    /**
     * 201 Created
     */
    protected function created(string $message = 'Created successfully', mixed $data = []): JsonResponse
    {
        return $this->success($message, $data, 201);
    }

    /**
     * 204 No Content
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Generic success response.
     */
    protected function success(string $message = 'Success', mixed $data = [], int $statusCode = 200, array $meta = []): JsonResponse
    {
        // Auto-handle paginated results (extract data + pagination meta)
        if ($data instanceof AbstractPaginator || $data instanceof ResourceCollection) {
            return $this->paginated($message, $data, $statusCode);
        }

        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Paginated success response — flattens Laravel's paginator into a
     * consistent data + meta structure.
     */
    protected function paginated(string $message, $paginator, int $statusCode = 200): JsonResponse
    {
        // If it's a ResourceCollection, resolve the underlying paginator for meta
        $resource = $paginator instanceof ResourceCollection
            ? $paginator->resource
            : $paginator;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator instanceof ResourceCollection
                ? $paginator->response()->getData(true)['data']
                : $paginator->items(),
            'meta' => [
                'current_page' => $resource->currentPage(),
                'last_page' => $resource->lastPage(),
                'per_page' => $resource->perPage(),
                'total' => $resource->total(),
                'from' => $resource->firstItem(),
                'to' => $resource->lastItem(),
            ],
        ], $statusCode);
    }

    /**
     * Generic error response.
     */
    protected function error(string $message = 'Something went wrong', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * 400 Bad Request
     */
    protected function badRequest(string $message = 'Bad request', mixed $errors = null): JsonResponse
    {
        return $this->error($message, 400, $errors);
    }

    /**
     * 401 Unauthorized
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * 403 Forbidden
     */
    protected function forbidden(string $message = 'This action is forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * 404 Not Found
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * 409 Conflict
     */
    protected function conflict(string $message = 'Conflict occurred'): JsonResponse
    {
        return $this->error($message, 409);
    }

    /**
     * 422 Unprocessable Entity — typically for validation errors.
     * Accepts a validator's ->errors()->toArray() or a plain array.
     */
    protected function validationError(mixed $errors, string $message = 'The given data was invalid'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * 429 Too Many Requests
     */
    protected function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return $this->error($message, 429);
    }

    /**
     * 500 Internal Server Error
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500);
    }
}