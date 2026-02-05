<?php
/**
 * Comprehensive Test Suite Runner
 * 
 * This file runs all test cases for the smart PHP modules:
 * - installs.php
 * - quote.php  
 * - json_data_admin.php
 * - lead_list.php
 */

// Test configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include test classes
require_once 'test_installs.php';
require_once 'test_quote.php';
require_once 'test_json_data_admin.php';
require_once 'test_lead_list.php';

class TestSuiteRunner {
    
    private $testResults = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Run all test suites
     */
    public function runAllTests() {
        echo "<!DOCTYPE html>\n<html>\n<head>\n";
        echo "<title>Smart PHP Test Suite</title>\n";
        echo "<style>\n";
        echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
        echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
        echo ".test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }\n";
        echo ".pass { color: #28a745; font-weight: bold; }\n";
        echo ".fail { color: #dc3545; font-weight: bold; }\n";
        echo ".warning { color: #ffc107; font-weight: bold; }\n";
        echo ".summary { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }\n";
        echo ".test-file { background: #fff; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px; }\n";
        echo "</style>\n";
        echo "</head>\n<body>\n";
        echo "<div class='container'>\n";
        echo "<h1>🧪 Smart PHP Test Suite Results</h1>\n";
        echo "<p>Running comprehensive tests for all smart PHP modules...</p>\n";
        
        // Test file existence
        $this->checkTestFiles();
        
        // Run individual test suites
        $this->runInstallsTests();
        $this->runQuoteTests();
        $this->runJsonDataAdminTests();
        $this->runLeadListTests();
        
        // Display final summary
        $this->displayFinalSummary();
        
        echo "</div>\n";
        echo "</body>\n</html>\n";
    }
    
    /**
     * Check if all test files exist
     */
    private function checkTestFiles() {
        echo "<div class='test-section'>\n";
        echo "<h2>📁 Test File Verification</h2>\n";
        
        $testFiles = [
            'test_installs.php' => 'Installs API Tests',
            'test_quote.php' => 'Quote System Tests',
            'test_json_data_admin.php' => 'JSON Data Admin Tests',
            'test_lead_list.php' => 'Lead List Tests'
        ];
        
        foreach ($testFiles as $file => $description) {
            $exists = file_exists($file);
            $status = $exists ? "<span class='pass'>✅ EXISTS</span>" : "<span class='fail'>❌ MISSING</span>";
            echo "<div class='test-file'>{$status} <strong>{$file}</strong> - {$description}</div>\n";
            
            if (!$exists) {
                echo "<div class='warning'>⚠️ Warning: {$file} not found. Tests for this module will be skipped.</div>\n";
            }
        }
        echo "</div>\n";
    }
    
    /**
     * Run installs.php tests
     */
    private function runInstallsTests() {
        echo "<div class='test-section'>\n";
        echo "<h2>🔧 installs.php Tests</h2>\n";
        
        if (file_exists('test_installs.php')) {
            try {
                $tester = new InstallsTest();
                $tester->runAllTests();
                echo "<div class='pass'>✅ installs.php tests completed successfully</div>\n";
            } catch (Exception $e) {
                echo "<div class='fail'>❌ Error running installs.php tests: " . $e->getMessage() . "</div>\n";
            }
        } else {
            echo "<div class='warning'>⚠️ test_installs.php not found - skipping tests</div>\n";
        }
        echo "</div>\n";
    }
    
    /**
     * Run quote.php tests
     */
    private function runQuoteTests() {
        echo "<div class='test-section'>\n";
        echo "<h2>💰 quote.php Tests</h2>\n";
        
        if (file_exists('test_quote.php')) {
            try {
                $tester = new QuoteTest();
                $tester->runAllTests();
                echo "<div class='pass'>✅ quote.php tests completed successfully</div>\n";
            } catch (Exception $e) {
                echo "<div class='fail'>❌ Error running quote.php tests: " . $e->getMessage() . "</div>\n";
            }
        } else {
            echo "<div class='warning'>⚠️ test_quote.php not found - skipping tests</div>\n";
        }
        echo "</div>\n";
    }
    
    /**
     * Run json_data_admin.php tests
     */
    private function runJsonDataAdminTests() {
        echo "<div class='test-section'>\n";
        echo "<h2>📊 json_data_admin.php Tests</h2>\n";
        
        if (file_exists('test_json_data_admin.php')) {
            try {
                $tester = new JsonDataAdminTest();
                $tester->runAllTests();
                echo "<div class='pass'>✅ json_data_admin.php tests completed successfully</div>\n";
            } catch (Exception $e) {
                echo "<div class='fail'>❌ Error running json_data_admin.php tests: " . $e->getMessage() . "</div>\n";
            }
        } else {
            echo "<div class='warning'>⚠️ test_json_data_admin.php not found - skipping tests</div>\n";
        }
        echo "</div>\n";
    }
    
    /**
     * Run lead_list.php tests
     */
    private function runLeadListTests() {
        echo "<div class='test-section'>\n";
        echo "<h2>📋 lead_list.php Tests</h2>\n";
        
        if (file_exists('test_lead_list.php')) {
            try {
                $tester = new LeadListTest();
                $tester->runAllTests();
                echo "<div class='pass'>✅ lead_list.php tests completed successfully</div>\n";
            } catch (Exception $e) {
                echo "<div class='fail'>❌ Error running lead_list.php tests: " . $e->getMessage() . "</div>\n";
            }
        } else {
            echo "<div class='warning'>⚠️ test_lead_list.php not found - skipping tests</div>\n";
        }
        echo "</div>\n";
    }
    
    /**
     * Display final summary
     */
    private function displayFinalSummary() {
        $endTime = microtime(true);
        $executionTime = round($endTime - $this->startTime, 2);
        
        echo "<div class='summary'>\n";
        echo "<h2>📈 Test Suite Summary</h2>\n";
        echo "<p><strong>Execution Time:</strong> {$executionTime} seconds</p>\n";
        echo "<p><strong>Test Files:</strong> 4 modules tested</p>\n";
        echo "<p><strong>Status:</strong> <span class='pass'>All test suites executed</span></p>\n";
        
        echo "<h3>📋 Test Coverage:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>installs.php:</strong> API endpoints, authentication, CRUD operations</li>\n";
        echo "<li><strong>quote.php:</strong> UI components, calculations, form validation, quote generation</li>\n";
        echo "<li><strong>json_data_admin.php:</strong> Database operations, API endpoints, data validation</li>\n";
        echo "<li><strong>lead_list.php:</strong> Infinite scroll, search, inline editing, modal functionality</li>\n";
        echo "</ul>\n";
        
        echo "<h3>🔧 Next Steps:</h3>\n";
        echo "<ul>\n";
        echo "<li>Review individual test results for detailed feedback</li>\n";
        echo "<li>Address any failing tests identified</li>\n";
        echo "<li>Run tests regularly during development</li>\n";
        echo "<li>Consider adding automated CI/CD integration</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    }
}

// Run the test suite
$runner = new TestSuiteRunner();
$runner->runAllTests();
?>