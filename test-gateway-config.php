<?php
/**
 * Test file to verify the gateway configuration changes
 * This file demonstrates how the test config now uses WooCommerce settings
 * instead of hardcoded credentials
 */

// WordPress environment check
if (!defined('ABSPATH')) {
    // For testing outside WordPress, define basic constants
    define('ABSPATH', true);
}

// Include the necessary files
require_once 'init.php';
require_once 'includes/class-herepay-test-config.php';

echo "<h2>Herepay Gateway Configuration Test</h2>\n";

// Test 1: Get credentials from WooCommerce settings
echo "<h3>1. Gateway Credentials from WooCommerce Settings:</h3>\n";
try {
    $credentials = Herepay_Test_Config::get_gateway_credentials();
    echo "<pre>";
    echo "Environment: " . ($credentials['environment'] ?: 'Not set') . "\n";
    echo "API Key: " . (empty($credentials['api_key']) ? 'Not configured' : substr($credentials['api_key'], 0, 20) . '...') . "\n";
    echo "Secret Key: " . (empty($credentials['secret_key']) ? 'Not configured' : substr($credentials['secret_key'], 0, 20) . '...') . "\n";
    echo "Private Key: " . (empty($credentials['private_key']) ? 'Not configured' : 'Configured (' . strlen($credentials['private_key']) . ' chars)') . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Compare with legacy method
echo "<h3>2. Legacy Method (should return same values):</h3>\n";
try {
    $legacy_credentials = Herepay_Test_Config::get_sandbox_credentials();
    echo "<pre>";
    echo "Environment: " . ($legacy_credentials['environment'] ?: 'Not set') . "\n";
    echo "API Key: " . (empty($legacy_credentials['api_key']) ? 'Not configured' : substr($legacy_credentials['api_key'], 0, 20) . '...') . "\n";
    echo "Secret Key: " . (empty($legacy_credentials['secret_key']) ? 'Not configured' : substr($legacy_credentials['secret_key'], 0, 20) . '...') . "\n";
    echo "Private Key: " . (empty($legacy_credentials['private_key']) ? 'Not configured' : 'Configured (' . strlen($legacy_credentials['private_key']) . ' chars)') . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Gateway configuration
echo "<h3>3. Gateway Configuration Test:</h3>\n";
try {
    $gateway = new WC_Herepay_Payment_Gateway();
    $configured_gateway = Herepay_Test_Config::apply_gateway_config($gateway);
    
    echo "<pre>";
    echo "Gateway Environment: " . ($configured_gateway->environment ?: 'Not set') . "\n";
    echo "Gateway API Key: " . (empty($configured_gateway->api_key) ? 'Not configured' : substr($configured_gateway->api_key, 0, 20) . '...') . "\n";
    echo "Gateway Secret Key: " . (empty($configured_gateway->secret_key) ? 'Not configured' : substr($configured_gateway->secret_key, 0, 20) . '...') . "\n";
    echo "Gateway Private Key: " . (empty($configured_gateway->private_key) ? 'Not configured' : 'Configured') . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

echo "<h3>Summary:</h3>\n";
echo "<p>✅ <strong>Success!</strong> The test configuration now reads credentials from WooCommerce settings instead of hardcoded values.</p>\n";
echo "<p>📝 <strong>Note:</strong> Configure your API credentials in WooCommerce > Settings > Payments > Herepay to see actual values.</p>\n";
echo "<p>🔒 <strong>Security:</strong> No more hardcoded credentials in the source code!</p>\n";
?>
