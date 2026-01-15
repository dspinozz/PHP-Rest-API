<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use RestApi\Api\ResponseFormatter;
use RestApi\Exceptions\ErrorCodes;

class ResponseFormatterTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = ResponseFormatter::success(['id' => 123]);
        
        $this->assertTrue($response['success']);
        $this->assertEquals(['id' => 123], $response['data']);
        $this->assertEquals(ErrorCodes::OK, $response['status']);
    }

    public function testErrorResponse(): void
    {
        $response = ResponseFormatter::error('Something went wrong', ErrorCodes::BAD_REQUEST);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Something went wrong', $response['error']);
        $this->assertEquals(ErrorCodes::BAD_REQUEST, $response['status']);
    }

    public function testErrorResponseWithDetails(): void
    {
        $errors = ['email' => 'Invalid email', 'password' => 'Too short'];
        $response = ResponseFormatter::error('Validation failed', ErrorCodes::UNPROCESSABLE_ENTITY, $errors);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertEquals($errors, $response['errors']);
    }

    public function testPaginatedResponse(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $response = ResponseFormatter::paginated($data, 1, 10, 25);
        
        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertEquals(1, $response['pagination']['page']);
        $this->assertEquals(10, $response['pagination']['per_page']);
        $this->assertEquals(25, $response['pagination']['total']);
        $this->assertEquals(3, $response['pagination']['total_pages']);
    }
}
