<?php
/**
 * Test Cases for json_data_admin.php
 * 
 * This file contains comprehensive test cases to verify the functionality
 * of the json_data_admin.php data management system.
 */

// Test configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

class JsonDataAdminTest {
    private $baseUrl;
    private $testResults = [];
    private $testDataFile;
    
    public function __construct($baseUrl = 'http://localhost:8081/smart/json_data_admin.php') {
        $this->baseUrl = $baseUrl;
        $this->testDataFile = __DIR__ . '/test_data.json';
        $this->createTestData();
    }
    
    /**
     * Create test data file
     */
    private function createTestData() {
        $testData = [
            'HDD' => [
                '1TB' => ['price' => 2500, 'warranty' => '3 years'],
                '2TB' => ['price' => 4500, 'warranty' => '3 years']
            ],
            'Type' => [
                'HIKVISION' => [
                    '4CH' => ['price' => 8000, 'cameras' => 4],
                    '8CH' => ['price' => 12000, 'cameras' => 8]
                ]
            ],
            'WIFI' => [
                '2MP' => ['price' => 2500, 'resolution' => '1920x1080'],
                '5MP' => ['price' => 4500, 'resolution' => '2560x1920']
            ],
            'Wireless' => [
                'Kit_A' => ['price' => 15000, 'cameras' => 4],
                'Kit_B' => ['price' => 25000, 'cameras' => 8]
            ],
            'items' => [
                'SMPS' => ['price' => 500, 'description' => 'Power Supply'],
                'Connector' => ['price' => 50, 'description' => 'BNC Connector']
            ]
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Run all test cases
     */
    public function runAllTests() {
        echo "<h1>Json Data Admin Test Suite</h1>\n";
        echo "<div style='font-family: monospace; margin: 20px;'>\n";
        
        // Database Connection Tests
        $this->testDatabaseConnection();
        
        // API Endpoint Tests
        $this->testGetEndpoint();
        $this->testSaveEndpoint();
        $this->testExportEndpoint();
        
        // Data Structure Tests
        $this->testDataStructure();
        $this->testDataValidation();
        
        // UI Component Tests
        $this->testUIComponents();
        
        // Cleanup
        $this->cleanupTestData();
        
        // Display results
        $this->displayResults();
        
        echo "</div>\n";
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        echo "<h2>🗄️ Database Connection Tests</h2>\n";
        
        // Test database connectivity
        $response = $this->makeRequest('GET', ['action' => 'get']);
        $httpCode = $this->getLastHttpCode();
        
        $this->assertTrue($httpCode === 200, 'Should connect to database successfully');
        $this->assertNotContains('Database Connection Failed', $response, 'Should not show connection errors');
    }
    
    /**
     * Test get endpoint
     */
    private function testGetEndpoint() {
        echo "<h2>📥 Get Endpoint Tests</h2>\n";
        
        // Test get action
        $response = $this->makeRequest('GET', ['action' => 'get']);
        $data = json_decode($response, true);
        
        $this->assertTrue(isset($data['ok']), 'Should return ok status');
        $this->assertEquals(true, $data['ok'], 'Should return success');
        $this->assertTrue(isset($data['updated_at']), 'Should return updated_at timestamp');
        $this->assertTrue(isset($data['data']), 'Should return data object');
        
        // Test data structure
        if (isset($data['data'])) {
            $this->assertTrue(is_array($data['data']), 'Data should be an array');
            $this->assertTrue(isset($data['data']['HDD']), 'Should have HDD category');
            $this->assertTrue(isset($data['data']['Type']), 'Should have Type category');
            $this->assertTrue(isset($data['data']['items']), 'Should have items category');
        }
    }
    
    /**
     * Test save endpoint
     */
    private function testSaveEndpoint() {
        echo "<h2>💾 Save Endpoint Tests</h2>\n";
        
        // Test valid save
        $testData = [
            'HDD' => [
                'Test_HDD' => ['price' => 9999, 'warranty' => 'test']
            ],
            'Type' => [
                'Test_Type' => ['price' => 8888, 'cameras' => 16]
            ],
            'items' => [
                'Test_Item' => ['price' => 777, 'description' => 'Test item']
            ]
        ];
        
        $response = $this->makeRequest('POST', ['action' => 'save'], $testData);
        $data = json_decode($response, true);
        
        $this->assertTrue(isset($data['ok']), 'Should return ok status');
        $this->assertEquals(true, $data['ok'], 'Should successfully save data');
        
        // Test invalid JSON
        $invalidJson = '{invalid json}';
        $response = $this->makeRawRequest('POST', ['action' => 'save'], $invalidJson);
        $data = json_decode($response, true);
        
        $this->assertTrue(isset($data['ok']), 'Should handle invalid JSON');
        $this->assertEquals(false, $data['ok'], 'Should return error for invalid JSON');
        $this->assertTrue(isset($data['error']), 'Should return error message');
    }
    
    /**
     * Test export endpoint
     */
    private function testExportEndpoint() {
        echo "<h2>📤 Export Endpoint Tests</h2>\n";
        
        // Test export action
        $response = $this->makeRequest('GET', ['action' => 'export']);
        $httpCode = $this->getLastHttpCode();
        $headers = $this->getLastHeaders();
        
        $this->assertTrue($httpCode === 200, 'Should return 200 status for export');
        $this->assertTrue($this->hasHeader($headers, 'Content-Type: application/json'), 'Should have JSON content type');
        $this->assertTrue($this->hasHeader($headers, 'Content-Disposition: attachment'), 'Should have attachment disposition');
        
        // Test exported data is valid JSON
        $exportedData = json_decode($response, true);
        $this->assertTrue($exportedData !== null, 'Exported data should be valid JSON');
        $this->assertTrue(is_array($exportedData), 'Exported data should be an array');
    }
    
    /**
     * Test data structure integrity
     */
    private function testDataStructure() {
        echo "<h2>🏗️ Data Structure Tests</h2>\n";
        
        $response = $this->makeRequest('GET', ['action' => 'get']);
        $data = json_decode($response, true);
        
        if (isset($data['data'])) {
            $categories = ['HDD', 'Type', 'WIFI', 'Wireless', 'items'];
            
            foreach ($categories as $category) {
                $this->assertTrue(isset($data['data'][$category]), "Should have {$category} category");
                
                if (isset($data['data'][$category])) {
                    $this->assertTrue(is_array($data['data'][$category]) || is_object($data['data'][$category]), 
                        "{$category} should be array or object");
                }
            }
            
            // Test specific data structure requirements
            if (isset($data['data']['HDD'])) {
                foreach ($data['data']['HDD'] as $hdd => $details) {
                    $this->assertTrue(isset($details['price']), "HDD {$hdd} should have price");
                    $this->assertTrue(is_numeric($details['price']), "HDD {$hdd} price should be numeric");
                }
            }
            
            if (isset($data['data']['Type'])) {
                foreach ($data['data']['Type'] as $type => $details) {
                    $this->assertTrue(isset($details['price']), "Type {$type} should have price");
                    $this->assertTrue(is_numeric($details['price']), "Type {$type} price should be numeric");
                }
            }
        }
    }
    
    /**
     * Test data validation
     */
    private function testDataValidation() {
        echo "<h2>✅ Data Validation Tests</h2>\n";
        
        // Test with invalid price data
        $invalidData = [
            'HDD' => [
                'Invalid_Price' => ['price' => 'not_a_number']
            ]
        ];
        
        $response = $this->makeRequest('POST', ['action' => 'save'], $invalidData);
        $data = json_decode($response, true);
        
        // The system should accept the data but we should validate on client side
        $this->assertTrue(isset($data['ok']), 'Should handle invalid price data');
        
        // Test with empty data
        $emptyData = [];
        $response = $this->makeRequest('POST', ['action' => 'save'], $emptyData);
        $data = json_decode($response, true);
        
        $this->assertTrue(isset($data['ok']), 'Should handle empty data');
    }
    
    /**
     * Test UI components
     */
    private function testUIComponents() {
        echo "<h2>🎨 UI Component Tests</h2>\n";
        
        $response = $this->makeRequest('GET');
        
        // Test HTML structure
        $this->assertContains('<!doctype html>', $response, 'Should be valid HTML5');
        $this->assertContains('<title>SM Pricing | Table Editor</title>', $response, 'Should have correct title');
        $this->assertContains('tailwindcss', $response, 'Should load Tailwind CSS');
        $this->assertContains('Inter', $response, 'Should use Inter font');
        
        // Test form elements
        $this->assertContains('id="app"', $response, 'Should have app container');
        $this->assertContains('v-for="(cat,idx) in tabs"', $response, 'Should have Vue.js template');
        $this->assertContains('@click="saveAll"', $response, 'Should have save button');
        $this->assertContains('@click="addRow"', $response, 'Should have add row button');
        
        // Test styling classes
        $this->assertContains('bg-gradient-to-br', $response, 'Should have gradient background');
        $this->assertContains('backdrop-filter', $response, 'Should have backdrop filter');
        $this->assertContains('hover:translate-y', $response, 'Should have hover effects');
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
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        $this->lastHttpCode = $httpCode;
        $this->lastHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        curl_close($ch);
        
        return $body;
    }
    
    /**
     * Make raw request without JSON encoding
     */
    private function makeRawRequest($method, $params = [], $rawData = null) {
        $url = $this->baseUrl;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($rawData) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        $this->lastHttpCode = $httpCode;
        $this->lastHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        curl_close($ch);
        
        return $body;
    }
    
    /**
     * Get last HTTP code
     */
    private function getLastHttpCode() {
        return $this->lastHttpCode ?? 200;
    }
    
    /**
     * Get last headers
     */
    private function getLastHeaders() {
        return $this->lastHeaders ?? '';
    }
    
    /**
     * Check if header exists
     */
    private function hasHeader($headers, $searchHeader) {
        return strpos($headers, $searchHeader) !== false;
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
    
    /**
     * Cleanup test data
     */
    private function cleanupTestData() {
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
    }
}

// Run tests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'test_json_data_admin.php') {
    $tester = new JsonDataAdminTest();
    $tester->runAllTests();
}
?>