<?php
// Simple test page to debug the admin connection issue
// Add this to test the admin connection functionality

if (!defined('ABSPATH')) {
    // If running outside WordPress, simulate the environment
    define('ABSPATH', true);
}

// Include the test configuration
include_once 'includes/class-herepay-test-config.php';

echo "<h1>Herepay Connection Test Debug</h1>";

echo "<h2>1. Test cURL Connection</h2>";
$curl_test = Herepay_Test_Config::test_curl_connection();
echo "<pre>";
print_r($curl_test);
echo "</pre>";

echo "<h2>2. Test Sandbox Credentials</h2>";
$credentials = Herepay_Test_Config::get_sandbox_credentials();
echo "<pre>";
echo "Environment: " . $credentials['environment'] . "\n";
echo "API Key: " . substr($credentials['api_key'], 0, 20) . "...\n";
echo "Secret Key: " . substr($credentials['secret_key'], 0, 20) . "...\n";
echo "Private Key: " . substr($credentials['private_key'], 0, 50) . "...\n";
echo "</pre>";

echo "<h2>3. Manual API Test</h2>";
$api_key = $credentials['api_key'];
$secret_key = $credentials['secret_key'];
$url = 'https://uat.herepay.org/api/v1/herepay/payment/channels';

$headers = [
    'Content-Type: application/json',
    'XApiKey: ' . $api_key,
    'SecretKey: ' . $secret_key
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
if ($error) {
    echo "cURL Error: $error<br>";
} else {
    echo "Response: " . htmlspecialchars(substr($response, 0, 200)) . "...<br>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        echo "✅ Success! Found " . count($data['data']) . " payment method groups<br>";
        if (isset($data['data'][0]['channels'])) {
            echo "Total channels: " . count($data['data'][0]['channels']) . "<br>";
        }
    }
}

echo "<h2>4. WordPress AJAX Simulation</h2>";
echo "The admin test button calls: wp-admin/admin-ajax.php?action=herepay_test_connection<br>";
echo "This should trigger the test_api_connection method in the admin class.<br>";
echo "Make sure WordPress is loading the plugin correctly.<br>";

?>
