
// /Users/dominic/Sites/localhost/smartronic/public_html/admin_v2/smart/tests.js

// Simple Test Runner
const tests = [];
const describe = (name, fn) => {
  console.group(`Suite: ${name}`);
  fn();
  console.groupEnd();
};
const it = (name, fn) => {
  try {
    fn();
    console.log(`✅ PASS: ${name}`);
  } catch (error) {
    console.error(`❌ FAIL: ${name}`);
    console.error(error);
  }
};
const assert = {
  equal(actual, expected, message = `Expected ${actual} to equal ${expected}`) {
    if (actual !== expected) {
      throw new Error(message);
    }
  },
  deepEqual(actual, expected, message = `Expected ${JSON.stringify(actual)} to deeply equal ${JSON.stringify(expected)}`) {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
      throw new Error(message);
    }
  },
  isTrue(value, message = `Expected ${value} to be true`) {
    if (value !== true) {
      throw new Error(message);
    }
  },
  isFalse(value, message = `Expected ${value} to be false`) {
    if (value !== false) {
      throw new Error(message);
    }
  },
};

/**
 * NOTE: To run these tests, you need to load them in an environment
 * where the functions from script.js are accessible.
 * This can be done by:
 * 1. Creating a test runner HTML file that includes script.js and then this test file.
 * 2. Manually exposing the functions from the IIFE in script.js to the global scope for testing.
 *
 * Example Test Runner HTML:
 * 
 * ```html
 * <!DOCTYPE html>
 * <html>
 * <head>
 *   <title>Test Runner</title>
 *   <!-- Mock DOM elements required by script.js -->
 *   <div id="summary-items"></div>
 *   <!-- ... other elements -->
 * </head>
 * <body>
 *   <script>
 *     // Mock the global 'data' object if needed by the functions under test
 *     const data = { Type: {}, HDD: {}, items: {} };
 *   </script>
 *   <script src="scripts.js"></script>
 *   <script src="tests.js"></script>
 * </body>
 * </html>
 * ```
 */

describe('Utility Functions', () => {

  it('should format numbers into INR currency strings', () => {
    assert.equal(formatINR(1000), '₹1,000', 'Should format 1000');
    assert.equal(formatINR(12345.67), '₹12,346', 'Should round and format a float');
    assert.equal(formatINR(0), '₹0', 'Should format 0');
    assert.equal(formatINR(null), '—', 'Should handle null');
    assert.equal(formatINR(undefined), '—', 'Should handle undefined');
    assert.equal(formatINR(NaN), '—', 'Should handle NaN');
  });

  it('should parse various price formats into a number', () => {
    assert.equal(parsePrice('1,234'), 1234, 'Should handle comma-separated string');
    assert.equal(parsePrice('₹500'), 500, 'Should handle currency symbol');
    assert.equal(parsePrice(999), 999, 'Should handle number');
    assert.equal(parsePrice('1,500.50'), 1500.50, 'Should handle decimals');
    assert.equal(parsePrice(null), null, 'Should handle null');
    assert.equal(parsePrice('invalid'), null, 'Should handle invalid string');
  });

});

describe('Sorting Functions', () => {

  it('should sort items by price from low to high', () => {
    const items = [
      { label: 'Item A', value: '500' },
      { label: 'Item B', value: 100 },
      { label: 'Item C', value: '₹1,200' },
      { label: 'Item D', value: null },
    ];
    const expected = [
      { label: 'Item D', value: null },
      { label: 'Item B', value: 100 },
      { label: 'Item A', value: '500' },
      { label: 'Item C', value: '₹1,200' },
    ];
    const sorted = sortByPriceLowToHigh(items);
    assert.deepEqual(sorted.map(i => i.label), ['Item D', 'Item B', 'Item A', 'Item C']);
  });

  it('should sort HDD data by price from low to high', () => {
    const hddData = [
      { label: '2TB', value: '8000' },
      { label: '1TB', value: 4500 },
      { label: '4TB', value: '₹15,000' },
    ];
    const expected = [
      { label: '1TB', value: 4500 },
      { label: '2TB', value: '8000' },
      { label: '4TB', value: '₹15,000' },
    ];
    const sorted = sortHddByPriceLowToHigh(hddData);
    assert.deepEqual(sorted.map(h => h.label), ['1TB', '2TB', '4TB']);
  });

});

console.log('Unit tests for scripts.js created.');
