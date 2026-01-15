<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Http;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use RestApi\Http\RequestHelper;

class RequestHelperTest extends TestCase
{
    public function testGetJsonBody(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $body = json_encode($data);
        
        $request = new ServerRequest('POST', '/test', [
            'Content-Type' => 'application/json'
        ], Stream::create($body));
        
        $result = RequestHelper::getJsonBody($request);
        
        $this->assertEquals($data, $result);
    }

    public function testGetJsonBodyInvalidJson(): void
    {
        $request = new ServerRequest('POST', '/test', [
            'Content-Type' => 'application/json'
        ], Stream::create('invalid json{'));
        
        $result = RequestHelper::getJsonBody($request);
        
        $this->assertNull($result);
    }

    public function testGetJsonBodyWrongContentType(): void
    {
        $request = new ServerRequest('POST', '/test', [
            'Content-Type' => 'text/plain'
        ], Stream::create('{"name": "John"}'));
        
        $result = RequestHelper::getJsonBody($request);
        
        $this->assertNull($result);
    }

    public function testGetJsonBodyEmpty(): void
    {
        $request = new ServerRequest('POST', '/test', [
            'Content-Type' => 'application/json'
        ], Stream::create(''));
        
        $result = RequestHelper::getJsonBody($request);
        
        $this->assertNull($result);
    }

    public function testGetQueryParam(): void
    {
        $request = new ServerRequest('GET', '/test?page=2&limit=10');
        
        $this->assertEquals('2', RequestHelper::getQueryParam($request, 'page'));
        $this->assertEquals('10', RequestHelper::getQueryParam($request, 'limit'));
        $this->assertNull(RequestHelper::getQueryParam($request, 'nonexistent'));
        $this->assertEquals('default', RequestHelper::getQueryParam($request, 'nonexistent', 'default'));
    }

    public function testGetQueryParams(): void
    {
        $request = new ServerRequest('GET', '/test?page=2&limit=10');
        
        $params = RequestHelper::getQueryParams($request);
        
        $this->assertEquals('2', $params['page']);
        $this->assertEquals('10', $params['limit']);
    }

    public function testGetParsedBody(): void
    {
        $data = ['name' => 'John', 'email' => 'john@test.com'];
        $request = new ServerRequest('POST', '/test');
        $request = $request->withParsedBody($data);
        
        $result = RequestHelper::getParsedBody($request);
        
        $this->assertEquals($data, $result);
    }

    public function testGetParsedBodyNull(): void
    {
        $request = new ServerRequest('POST', '/test');
        
        $result = RequestHelper::getParsedBody($request);
        
        $this->assertNull($result);
    }
}
