<?php

declare(strict_types=1);

namespace RestApi\Tests\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RestApi\Application;
use RestApi\Http\ResponseFactory;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
    }

    public function testBasicRoute(): void
    {
        $this->app->get('/test', function($request) {
            return ['message' => 'Hello World'];
        });

        $request = new ServerRequest('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello World', (string)$response->getBody());
    }

    public function testRouteWithParameters(): void
    {
        $this->app->get('/users/{id}', function($request, $params) {
            return ['id' => $params['id']];
        });

        $request = new ServerRequest('GET', '/users/123');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('123', $body['id']);
    }

    public function testNotFoundRoute(): void
    {
        $request = new ServerRequest('GET', '/nonexistent');
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPostRoute(): void
    {
        $this->app->post('/users', function($request) {
            return ['created' => true];
        });

        $request = new ServerRequest('POST', '/users');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
