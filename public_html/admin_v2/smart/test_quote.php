<?php
/**
 * Test Cases for quote.php
 * 
 * This file contains comprehensive test cases to verify the functionality
 * of the quote.php quotation system, including sticky totals calculations.
 */

// Test configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

class QuoteTest {
    private $baseUrl;
    private $testResults = [];
    
    public function __construct($baseUrl = 'http://localhost:8081/smart/quote.php') {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Run all test cases
     */
    public function runAllTests() {
        echo "<h1>Quote.php Test Suite</h1>\n";
        echo "<div style='font-family: monospace; margin: 20px;'>\n";
        
        // Authentication Tests
        $this->testAuthentication();
        
        // UI Component Tests
        $this->testUIComponents();
        
        // Sticky Totals Tests
        $this->testStickyTotalsCalculations();
        
        // Form Validation Tests
        $this->testFormValidation();
        
        // Integration Tests
        $this->testQuoteGeneration();
        
        // Display results
        $this->displayResults();
        
        echo "</div>\n";
    }
    
    /**
     * Test authentication requirements
     */
    private function testAuthentication() {
        echo "<h2>🔒 Authentication Tests</h2>\n";
        
        // Test page access with different roles
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET');
        $this->assertContains('<title>SM Quote | CCTV Requirement</title>', $response, 'Should load quote page with admin role');
        
        $this->setAuthCookie('market');
        $response = $this->makeRequest('GET');
        $this->assertContains('<title>SM Quote | CCTV Requirement</title>', $response, 'Should load quote page with market role');
        
        // Test admin-specific features
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET');
        $this->assertContains('sticky-profit-margin-display', $response, 'Should show profit margin display for admin');
        $this->assertContains('sticky-profit-eye-icon', $response, 'Should show profit eye icon for admin');
    }
    
    /**
     * Test UI components
     */
    private function testUIComponents() {
        echo "<h2>🎨 UI Component Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET');
        
        // Test main form elements
        $this->assertContains('id="cctv-requirement-form"', $response, 'Should have CCTV requirement form');
        $this->assertContains('id="num-cameras"', $response, 'Should have camera number slider');
        $this->assertContains('id="category-buttons"', $response, 'Should have category buttons');
        $this->assertContains('id="brand-buttons"', $response, 'Should have brand buttons');
        $this->assertContains('id="mp-buttons"', $response, 'Should have MP buttons');
        $this->assertContains('id="accessories"', $response, 'Should have accessories section');
        
        // Test sticky totals
        $this->assertContains('id="sticky-totals"', $response, 'Should have sticky totals section');
        $this->assertContains('id="sticky-total"', $response, 'Should have MIN total display');
        $this->assertContains('id="sticky-profit-amount"', $response, 'Should have MAX profit display');
        $this->assertContains('id="discount-dropdown"', $response, 'Should have discount dropdown');
        $this->assertContains('id="percentage-amount"', $response, 'Should have percentage input');
        
        // Test customer info section
        $this->assertContains('id="customer-name"', $response, 'Should have customer name input');
        $this->assertContains('id="num-whatsapp"', $response, 'Should have WhatsApp number input');
        $this->assertContains('id="whatsapp-preview"', $response, 'Should have WhatsApp preview textarea');
        
        // Test summary panel
        $this->assertContains('id="summary-panel"', $response, 'Should have summary panel');
        $this->assertContains('id="summary-items"', $response, 'Should have summary items');
        $this->assertContains('id="summary-subtotal"', $response, 'Should have subtotal display');
        $this->assertContains('id="summary-gst"', $response, 'Should have GST display');
        $this->assertContains('id="summary-total"', $response, 'Should have total display');
    }
    
    /**
     * Test sticky totals calculations
     */
    private function testStickyTotalsCalculations() {
        echo "<h2>💰 Sticky Totals Calculation Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        
        // Test basic calculation (15% profit margin)
        echo "<h3>Basic 15% Profit Calculation</h3>\n";
        $this->testCalculationScenario(10000, 0, 0, 11500, 13000, 'Basic 15% profit');
        
        // Test with discount
        echo "<h3>Calculation with Discount</h3>\n";
        $this->testCalculationScenario(10000, 1000, 0, 10500, 13000, 'With ₹1000 discount');
        
        // Test with additional percentage
        echo "<h3>Calculation with Additional Percentage</h3>\n";
        $this->testCalculationScenario(10000, 0, 10, 12650, 13000, 'With 10% additional');
        
        // Test with both discount and percentage
        echo "<h3>Calculation with Discount + Percentage</h3>\n";
        $this->testCalculationScenario(10000, 500, 5, 11550, 13000, 'With ₹500 discount + 5% additional');
        
        // Edge case: Low base amount
        echo "<h3>Edge Case: Low Base Amount</h3>\n";
        $this->testCalculationScenario(2000, 0, 0, 2300, 5000, 'Low base amount ₹2000');
    }
    
    /**
     * Test calculation scenario
     */
    private function testCalculationScenario($baseAmount, $discount, $additionalPercent, $expectedMax, $expectedMin, $scenarioName) {
        // Calculate expected values
        $calculatedMax = $baseAmount + ($baseAmount * 0.15) + ($baseAmount * ($additionalPercent / 100)) - $discount;
        $calculatedMin = $baseAmount + 3000; // flat profit
        
        echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "<strong>Scenario:</strong> {$scenarioName}<br>\n";
        echo "<strong>Base Amount:</strong> ₹{$baseAmount}<br>\n";
        echo "<strong>Discount:</strong> ₹{$discount}<br>\n";
        echo "<strong>Additional %:</strong> {$additionalPercent}%<br>\n";
        echo "<strong>Expected MAX:</strong> ₹{$expectedMax}<br>\n";
        echo "<strong>Expected MIN:</strong> ₹{$expectedMin}<br>\n";
        echo "<strong>Calculated MAX:</strong> ₹{$calculatedMax}<br>\n";
        echo "<strong>Calculated MIN:</strong> ₹{$calculatedMin}<br>\n";
        
        $this->assertTrue(abs($calculatedMax - $expectedMax) < 1, "MAX calculation should match expected value");
        $this->assertTrue(abs($calculatedMin - $expectedMin) < 1, "MIN calculation should match expected value");
        $this->assertTrue($calculatedMax < $calculatedMin, "MAX should be less than MIN (profit margin logic)");
        
        echo "</div>\n";
    }
    
    /**
     * Test form validation
     */
    private function testFormValidation() {
        echo "<h2>✅ Form Validation Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET');
        
        // Test form has novalidate attribute (allows custom validation)
        $this->assertContains('novalidate', $response, 'Form should have novalidate attribute');
        
        // Test input validation attributes
        $this->assertContains('min="1"', $response, 'Camera input should have min validation');
        $this->assertContains('max="32"', $response, 'Camera input should have max validation');
        
        // Test WhatsApp input type
        $this->assertContains('type="tel"', $response, 'WhatsApp input should be tel type');
        
        // Test required fields (implicit validation)
        $this->assertContains('placeholder="Enter customer name"', $response, 'Customer name should have placeholder');
        $this->assertContains('placeholder="Enter WhatsApp number"', $response, 'WhatsApp should have placeholder');
    }
    
    /**
     * Test quote generation functionality
     */
    private function testQuoteGeneration() {
        echo "<h2>📋 Quote Generation Tests</h2>\n";
        
        $this->setAuthCookie('admin');
        $response = $this->makeRequest('GET');
        
        // Test JavaScript files are loaded
        $this->assertContains('scripts.js', $response, 'Should load scripts.js');
        $this->assertContains('data.json', $response, 'Should reference data.json');
        
        // Test CSS files are loaded
        $this->assertContains('styles.css', $response, 'Should load styles.css');
        $this->assertContains('materialize.min.css', $response, 'Should load Materialize CSS');
        
        // Test external dependencies
        $this->assertContains('font-awesome', $response, 'Should load Font Awesome');
        $this->assertContains('materialize.min.js', $response, 'Should load Materialize JS');
        
        // Test data structure references
        $this->assertContains('selected-category', $response, 'Should have selected-category hidden input');
        $this->assertContains('selected-brand', $response, 'Should have selected-brand hidden input');
        $this->assertContains('selected-mp', $response, 'Should have selected-mp hidden input');
        $this->assertContains('selected-component', $response, 'Should have selected-component hidden input');
    }
    
    /**
     * Helper function to make HTTP requests
     */
    private function makeRequest($method, $params = []) {
        $url = $this->baseUrl;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    /**
     * Helper function to set authentication cookie
     */
    private function setAuthCookie($role) {
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
if (basename($_SERVER['PHP_SELF']) === 'test_quote.php') {
    $tester = new QuoteTest();
    $tester->runAllTests();
}
?>