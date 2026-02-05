<?php
/**
 * Test Cases for installs.php
 * 
 * This file contains comprehensive test cases to verify the functionality
 * of the installs.php API endpoints and features.
 */

// Test configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

class InstallsTest {
    private $baseUrl;
    private $testResults = [];
    
    public function __construct($baseUrl = 'http://localhost:8081/smart/installs.php') {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Run all test cases
     */ 
    public function runAllTests() {
        echo "<h1>Installs.php Test Suite</h1>\n";
        echo "<div style='font-family: monospace; margin: 20px;'>\n";
        
        // Authentication Tests
        $this->testAuthentication();
        
        // API Endpoint Tests
        $this->testGetExtrasEndpoint();
        $this->testGetPaymentEndpoint();
        $this->testGetRecordByIdEndpoint();
        $this->testPaymentUpdateEndpoint();
        $this->testExtrasUpdateEndpoint();
        
        // Display results
        $this->displayResults();
        
        echo "</div>\n";
    }
    
    /**
     * Test authentication requirements
     */
    private function testAuthentication() {
        echo "<h2>🔒 Authentication Tests</h2>\n";
        
        // Test without authentication cookie
        $response = $this->makeRequest('GET', []);
        $this->assertContains('No access', $response, 'Should deny access without auth cookie');
        
        // Test with invalid auth role
        $this->setAuthCookie('invalid_role');
        $response = $this->makeRequest('GET', []);
        $this->assertContains('No access', $response, 'Should deny access with invalid role');
        
        // Test with admin role
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET', []);
        $this->assertNotContains('No access', $response, 'Should allow access with admin role');
        
        // Test with market role
        $this->setAuthCookie('market');
        $response = $this->makeRequest('GET', []);
        $this->assertNotContains('No access', $response, 'Should allow access with market role');
    }
    
    /**
     * Test get_extras endpoint
     */
    private function testGetExtrasEndpoint() {
        echo "<h2>📋 Get Extras Endpoint Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test with valid ID
        $response = $this->makeRequest('GET', ['get_extras' => '1', 'id' => 'TEST001']);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should return success status');
        $this->assertTrue(isset($data['extras']), 'Should return extras field');
        
        // Test without ID parameter
        $response = $this->makeRequest('GET', ['get_extras' => '1']);
        $this->assertContains('DB connection failed', $response, 'Should handle missing ID');
        
        // Test with invalid ID
        $response = $this->makeRequest('GET', ['get_extras' => '1', 'id' => 'INVALID_ID']);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should handle invalid ID gracefully');
    }
    
    /**
     * Test get_payment endpoint
     */
    private function testGetPaymentEndpoint() {
        echo "<h2>💳 Get Payment Endpoint Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test with valid ID
        $response = $this->makeRequest('GET', ['get_payment' => '1', 'id' => 'TEST001']);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should return success status');
        $this->assertTrue(isset($data['amount_paid']), 'Should return amount_paid field');
        $this->assertTrue(isset($data['fully_paid']), 'Should return fully_paid field');
        
        // Test without ID parameter
        $response = $this->makeRequest('GET', ['get_payment' => '1']);
        $this->assertContains('DB connection failed', $response, 'Should handle missing ID');
    }
    
    /**
     * Test get record by ID endpoint
     */
    private function testGetRecordByIdEndpoint() {
        echo "<h2>🔍 Get Record by ID Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test with valid ID
        $response = $this->makeRequest('GET', ['id' => 'TEST001']);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['id']), 'Should return ID field');
        $this->assertTrue(isset($data['phone']), 'Should return phone field');
        $this->assertTrue(isset($data['name']), 'Should return name field');
        
        // Test with invalid ID
        $response = $this->makeRequest('GET', ['id' => 'INVALID_ID']);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['error']), 'Should return error for invalid ID');
        $this->assertEquals('Record not found', $data['error'], 'Should return specific error message');
    }
    
    /**
     * Test payment update endpoint
     */
    private function testPaymentUpdateEndpoint() {
        echo "<h2>💰 Payment Update Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test valid payment update
        $payload = [
            'updatePayment' => true,
            'id' => 'TEST001',
            'amount_paid' => 5000.00,
            'fully_paid' => false
        ];
        $response = $this->makeRequest('PATCH', [], $payload);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should return success status');
        $this->assertEquals(true, $data['success'], 'Should successfully update payment');
        
        // Test with missing ID
        $payload = [
            'updatePayment' => true,
            'amount_paid' => 5000.00
        ];
        $response = $this->makeRequest('PATCH', [], $payload);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should handle missing ID');
    }
    
    /**
     * Test extras update endpoint
     */
    private function testExtrasUpdateEndpoint() {
        echo "<h2>📝 Extras Update Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test valid extras update
        $payload = [
            'extrasOnly' => true,
            'id' => 'TEST001',
            'extras' => 'Additional camera, Extra cable'
        ];
        $response = $this->makeRequest('POST', [], $payload);
        $data = json_decode($response, true);
        $this->assertTrue(isset($data['success']), 'Should return success status');
        $this->assertEquals(true, $data['success'], 'Should successfully update extras');
    }
    
    /**
     * Helper function to make HTTP requests
     */
    private function makeRequest($method, $params = [], $payload = null) {
        $url = $this->baseUrl;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        // Send auth_role cookie if set
        if (isset($_COOKIE['auth_role'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'auth_role=' . $_COOKIE['auth_role']);
        }
        
        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload) {
                // If PATCH, send as form data (application/x-www-form-urlencoded)
                if ($method === 'PATCH') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                }
            }
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    /**
     * Helper function to set authentication cookie
     */
    private function setAuthCookie($role) {
        // In a real test environment, you would set the actual cookie
        // For this test, we'll simulate it by modifying the request
        $_COOKIE['auth_role'] = $role;
    }
    
    /**
     * Assertion helper
     */
    private function assertTrue($condition, $message) {
        $result = $condition ? '✅ PASS' : '❌ FAIL';
        echo "<div style='margin: 5px 0;'>{$result}: {$message}</div>\n";
        $this->testResults[] = ['test' => $message, 'result' => $condition];
    }
    
    /**
     * Assertion helper for string contains
     */
    private function assertContains($needle, $haystack, $message) {
        $result = strpos($haystack, $needle) !== false;
        $this->assertTrue($result, $message);
    }
    
    /**
     * Assertion helper for string not contains
     */
    private function assertNotContains($needle, $haystack, $message) {
        $result = strpos($haystack, $needle) === false;
        $this->assertTrue($result, $message);
    }
    
    /**
     * Display test results summary
     */
    private function displayResults() {
        $total = count($this->testResults);
        $passed = array_filter($this->testResults, function($r) { return $r['result']; });
        $failed = array_filter($this->testResults, function($r) { return !$r['result']; });
        
        echo "<h2>📊 Test Results Summary</h2>\n";
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<strong>Total Tests:</strong> {$total}<br>\n";
        echo "<strong style='color: green;'>Passed:</strong> " . count($passed) . "<br>\n";
        echo "<strong style='color: red;'>Failed:</strong> " . count($failed) . "<br>\n";
        echo "<strong>Success Rate:</strong> " . round((count($passed) / $total) * 100, 2) . "%\n";
        echo "</div>\n";
    }
}

// Run tests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'test_installs.php') {
    $tester = new InstallsTest();
    $tester->runAllTests();
}
?>