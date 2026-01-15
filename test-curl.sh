#!/bin/bash

# Curl Test Script for PHP REST API Framework
# Usage: ./test-curl.sh [base_url]
# Default: http://localhost:8080

BASE_URL="${1:-http://localhost:9000}"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "PHP REST API Framework - Curl Tests"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo ""

# Test counter
PASSED=0
FAILED=0

test_endpoint() {
    local name="$1"
    local method="$2"
    local url="$3"
    local data="$4"
    local expected_status="$5"
    local description="$6"
    
    echo -n "Testing: $name ... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" "$url")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -X POST \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$url")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "$expected_status" ]; then
        echo -e "${GREEN}✓ PASS${NC} (Status: $http_code)"
        echo "  Response: $(echo "$body" | head -c 100)..."
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Expected: $expected_status, Got: $http_code)"
        echo "  Response: $body"
        ((FAILED++))
        return 1
    fi
}

# Test 1: Health Check
echo "1. Health Check Endpoint"
test_endpoint \
    "GET /" \
    "GET" \
    "$BASE_URL/" \
    "" \
    "200" \
    "Health check endpoint"

echo ""

# Test 2: Echo Endpoint
echo "2. Echo Endpoint with Parameter"
test_endpoint \
    "GET /echo/hello" \
    "GET" \
    "$BASE_URL/echo/hello" \
    "" \
    "200" \
    "Echo endpoint with path parameter"

echo ""

# Test 3: JSON POST
echo "3. JSON POST Endpoint"
test_endpoint \
    "POST /test" \
    "POST" \
    "$BASE_URL/test" \
    '{"message": "test", "value": 123}' \
    "200" \
    "JSON POST endpoint"

echo ""

# Test 4: Validation - Valid Data
echo "4. Validation Endpoint - Valid Data"
test_endpoint \
    "POST /validate (valid)" \
    "POST" \
    "$BASE_URL/validate" \
    '{"email": "test@example.com", "name": "John Doe"}' \
    "200" \
    "Validation with valid data"

echo ""

# Test 5: Validation - Invalid Data
echo "5. Validation Endpoint - Invalid Data"
test_endpoint \
    "POST /validate (invalid)" \
    "POST" \
    "$BASE_URL/validate" \
    '{"email": "invalid-email", "name": "A"}' \
    "422" \
    "Validation with invalid data (should fail)"

echo ""

# Test 6: Login - Valid Credentials
echo "6. Login Endpoint - Valid Credentials"
response=$(curl -s -w "\n%{http_code}" -X POST \
    -H "Content-Type: application/json" \
    -d '{"email": "test@example.com", "password": "password123"}' \
    "$BASE_URL/login")

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✓ PASS${NC} (Status: $http_code)"
    TOKEN=$(echo "$body" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    echo "  Token received: ${TOKEN:0:20}..."
    ((PASSED++))
    
    # Test 7: Protected Route with Token
    echo ""
    echo "7. Protected Route - With Valid Token"
    if [ -n "$TOKEN" ]; then
        test_endpoint \
            "GET /protected (with token)" \
            "GET" \
            "$BASE_URL/protected" \
            "" \
            "200" \
            "Protected route with valid JWT token"
        
        # Add Authorization header manually for protected route
        response=$(curl -s -w "\n%{http_code}" \
            -H "Authorization: Bearer $TOKEN" \
            "$BASE_URL/protected")
        
        http_code=$(echo "$response" | tail -n1)
        body=$(echo "$response" | sed '$d')
        
        if [ "$http_code" = "200" ]; then
            echo -e "${GREEN}✓ PASS${NC} (Status: $http_code)"
            echo "  Response: $(echo "$body" | head -c 100)..."
            ((PASSED++))
        else
            echo -e "${RED}✗ FAIL${NC} (Expected: 200, Got: $http_code)"
            echo "  Response: $body"
            ((FAILED++))
        fi
    fi
else
    echo -e "${RED}✗ FAIL${NC} (Expected: 200, Got: $http_code)"
    echo "  Response: $body"
    ((FAILED++))
fi

echo ""

# Test 8: Protected Route - No Token
echo "8. Protected Route - Without Token"
test_endpoint \
    "GET /protected (no token)" \
    "GET" \
    "$BASE_URL/protected" \
    "" \
    "401" \
    "Protected route without token (should fail)"

echo ""

# Test 9: Login - Invalid Credentials
echo "9. Login Endpoint - Invalid Credentials"
test_endpoint \
    "POST /login (invalid)" \
    "POST" \
    "$BASE_URL/login" \
    '{"email": "wrong@example.com", "password": "wrong"}' \
    "401" \
    "Login with invalid credentials (should fail)"

echo ""

# Test 10: 404 Not Found
echo "10. Not Found Endpoint"
test_endpoint \
    "GET /nonexistent" \
    "GET" \
    "$BASE_URL/nonexistent" \
    "" \
    "404" \
    "Non-existent endpoint (should return 404)"

echo ""
echo "=========================================="
echo "Test Results:"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo "Total: $((PASSED + FAILED))"
echo "=========================================="

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed! ✗${NC}"
    exit 1
fi
