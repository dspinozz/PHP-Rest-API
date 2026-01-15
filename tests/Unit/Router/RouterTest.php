<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Router;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RestApi\Router\Router;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testAddAndMatchRoute(): void
    {
        $handler = function() { return 'test'; };
        $this->router->addRoute('GET', '/users', $handler);
        
        $request = new ServerRequest('GET', '/users');
        $route = $this->router->match($request);
        
        $this->assertNotNull($route);
        $this->assertEquals('GET', $route->getMethod());
        $this->assertEquals('/users', $route->getPath());
    }

    public function testNoMatchForUnregisteredRoute(): void
    {
        $this->router->addRoute('GET', '/users', function() {});
        
        $request = new ServerRequest('GET', '/posts');
        $route = $this->router->match($request);
        
        $this->assertNull($route);
    }

    public function testMatchWithParameters(): void
    {
        $this->router->addRoute('GET', '/users/{id}', function() {});
        
        $request = new ServerRequest('GET', '/users/123');
        $route = $this->router->match($request);
        
        $this->assertNotNull($route);
        $this->assertEquals(['id' => '123'], $route->getParams());
    }

    public function testMultipleRoutes(): void
    {
        $this->router->addRoute('GET', '/users', function() {});
        $this->router->addRoute('POST', '/users', function() {});
        $this->router->addRoute('GET', '/posts', function() {});
        
        $getRequest = new ServerRequest('GET', '/users');
        $postRequest = new ServerRequest('POST', '/users');
        $postsRequest = new ServerRequest('GET', '/posts');
        
        $this->assertNotNull($this->router->match($getRequest));
        $this->assertNotNull($this->router->match($postRequest));
        $this->assertNotNull($this->router->match($postsRequest));
    }
}
