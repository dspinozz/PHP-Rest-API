<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use RestApi\Http\ServerRequestFactory;

class ServerRequestFactoryTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $request = ServerRequestFactory::create(
            'POST',
            '/test',
            ['REMOTE_ADDR' => '127.0.0.1'],
            ['Content-Type' => 'application/json'],
            '{"test": "data"}'
        );
        
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/test', $request->getUri()->getPath());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('{"test": "data"}', (string)$request->getBody());
    }

    public function testCreateRequestWithQueryParams(): void
    {
        $request = ServerRequestFactory::create(
            'GET',
            '/test',
            [],
            [],
            null,
            '1.1',
            [],
            ['page' => '1', 'limit' => '10']
        );
        
        $this->assertEquals('1', $request->getQueryParams()['page']);
        $this->assertEquals('10', $request->getQueryParams()['limit']);
    }
}
