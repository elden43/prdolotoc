<?php

namespace App\EventListener;

use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts exceptions to consistent JSON error responses as defined in ARCHITECTURE.md §2.4.
 *
 * Shape:
 *   { "error": "...", "message": "...", "details": { ... } }
 *
 * - NotFoundHttpException  → 404, error="not_found"
 * - ValidationException    → 400, error="validation_failed", details included
 * - Anything else          → 500, error="server_error"
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class JsonExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof NotFoundHttpException) {
            $response = new JsonResponse([
                'error'   => 'not_found',
                'message' => $throwable->getMessage() ?: 'Resource not found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        } elseif ($throwable instanceof ValidationException) {
            $body = [
                'error'   => 'validation_failed',
                'message' => $throwable->getMessage(),
            ];
            $details = $throwable->getDetails();
            if ($details !== []) {
                $body['details'] = $details;
            }
            $response = new JsonResponse($body, JsonResponse::HTTP_BAD_REQUEST);
        } else {
            $response = new JsonResponse([
                'error'   => 'server_error',
                'message' => 'Unexpected error. Please try again later.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
