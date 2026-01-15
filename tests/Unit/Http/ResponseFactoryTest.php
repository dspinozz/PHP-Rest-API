<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use RestApi\Http\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function testJsonResponse(): void
    {
        $data = ['message' => 'Hello World'];
        $response = $this->factory->json($data);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"Hello World"}', (string)$response->getBody());
    }

    public function testJsonResponseWithCustomStatusCode(): void
    {
        $data = ['error' => 'Not Found'];
        $response = $this->factory->json($data, 404);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testNotFoundResponse(): void
    {
        $response = $this->factory->notFound();
        
        $this->assertEquals(404, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Not Found', $body['error']);
    }

    public function testErrorResponse(): void
    {
        $response = $this->factory->error('Internal Server Error', 500);
        
        $this->assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Internal Server Error', $body['error']);
    }

    public function testSuccessResponse(): void
    {
        $data = ['id' => 123, 'name' => 'Test'];
        $response = $this->factory->success($data);
        
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals($data, $body);
    }

    public function testTextResponse(): void
    {
        $response = $this->factory->text('Hello World');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('Hello World', (string)$response->getBody());
    }

    public function testEmptyResponse(): void
    {
        $response = $this->factory->empty();
        
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', (string)$response->getBody());
    }
}
