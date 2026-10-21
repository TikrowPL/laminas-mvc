<?php

namespace LaminasTest\Mvc\Controller;

use Laminas\Mvc\Controller\MiddlewareController;
use Laminas\Router\RouteMatch;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class MiddlewareControllerCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ServerRequest::class)) {
            self::markTestSkipped('Install nyholm/psr7 to exercise optional middleware request integration.');
        }
    }

    public function testMissingRouteMatchPreservesTheRequest(): void
    {
        $controller = (new ReflectionClass(MiddlewareController::class))->newInstanceWithoutConstructor();
        $method     = new ReflectionMethod(MiddlewareController::class, 'populateRequestParametersFromRoute');
        $request    = new ServerRequest('GET', '/');

        self::assertSame($request, $method->invoke($controller, $request));
        self::assertSame($request, $method->invoke($controller, $request, null));
    }

    public function testRouteParametersBecomeImmutableRequestAttributes(): void
    {
        $controller = (new ReflectionClass(MiddlewareController::class))->newInstanceWithoutConstructor();
        $method     = new ReflectionMethod(MiddlewareController::class, 'populateRequestParametersFromRoute');
        $request    = (new ServerRequest('GET', '/'))->withAttribute('existing', 'preserved');
        $route      = new RouteMatch(['id' => 42, 'action' => 'view']);
        $result     = $method->invoke($controller, $request, $route);

        self::assertNotSame($request, $result);
        self::assertSame(['existing' => 'preserved', 'id' => 42, 'action' => 'view'], $result->getAttributes());
        self::assertSame(['existing' => 'preserved'], $request->getAttributes());
    }
}
