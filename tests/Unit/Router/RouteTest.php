<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Router;

use PHPUnit\Framework\TestCase;
use RestApi\Router\Route;

class RouteTest extends TestCase
{
    public function testExactPathMatch(): void
    {
        $route = new Route('GET', '/users', function() {});
        
        $this->assertTrue($route->matches('GET', '/users'));
        $this->assertFalse($route->matches('POST', '/users'));
        $this->assertFalse($route->matches('GET', '/posts'));
    }

    public function testParameterExtraction(): void
    {
        $route = new Route('GET', '/users/{id}', function() {});
        
        $this->assertTrue($route->matches('GET', '/users/123'));
        $this->assertEquals(['id' => '123'], $route->getParams());
    }

    public function testMultipleParameters(): void
    {
        $route = new Route('GET', '/users/{userId}/posts/{postId}', function() {});
        
        $this->assertTrue($route->matches('GET', '/users/123/posts/456'));
        $params = $route->getParams();
        $this->assertEquals('123', $params['userId']);
        $this->assertEquals('456', $params['postId']);
    }

    public function testMethodCaseInsensitive(): void
    {
        $route = new Route('get', '/users', function() {});
        
        $this->assertTrue($route->matches('GET', '/users'));
        $this->assertTrue($route->matches('get', '/users'));
    }

    public function testNoMatchForDifferentPath(): void
    {
        $route = new Route('GET', '/users/{id}', function() {});
        
        $this->assertFalse($route->matches('GET', '/users'));
        $this->assertFalse($route->matches('GET', '/users/123/comments'));
    }
}
