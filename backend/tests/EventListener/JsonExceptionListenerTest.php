<?php

namespace App\Tests\EventListener;

use App\EventListener\JsonExceptionListener;
use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class JsonExceptionListenerTest extends TestCase
{
    private JsonExceptionListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->listener = new JsonExceptionListener();
        $this->kernel   = $this->createMock(HttpKernelInterface::class);
    }

    private function makeEvent(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->kernel,
            Request::create('/test'),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }

    public function testNotFoundExceptionReturns404WithCorrectShape(): void
    {
        $event = $this->makeEvent(new NotFoundHttpException('SpinConfig not found'));
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('not_found', $body['error']);
        self::assertSame('SpinConfig not found', $body['message']);
        self::assertArrayNotHasKey('details', $body);
    }

    public function testValidationExceptionReturns400WithDetails(): void
    {
        $details   = ['name' => ['This value should not be blank.'], 'options' => ['At least one option is required.']];
        $exception = new ValidationException($details);
        $event     = $this->makeEvent($exception);
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('validation_failed', $body['error']);
        self::assertSame('Request validation failed.', $body['message']);
        self::assertArrayHasKey('details', $body);
        self::assertSame(['This value should not be blank.'], $body['details']['name']);
        self::assertSame(['At least one option is required.'], $body['details']['options']);
    }

    public function testValidationExceptionWithoutDetailsOmitsDetailsKey(): void
    {
        $event = $this->makeEvent(new ValidationException());
        $this->listener->onKernelException($event);

        $body = json_decode((string) $event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('details', $body);
    }

    public function testGenericExceptionReturns500WithServerErrorShape(): void
    {
        $event = $this->makeEvent(new \RuntimeException('Something went wrong'));
        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('server_error', $body['error']);
        self::assertArrayHasKey('message', $body);
        self::assertArrayNotHasKey('details', $body);
    }
}
