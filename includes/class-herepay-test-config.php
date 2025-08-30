<?php
/**
 * Herepay Test Configuration
 * This file contains sandbox credentials for testing the integration
 * DO NOT use these credentials in production!
 */

if (!defined('ABSPATH')) {
    exit;
}

class Herepay_Test_Config {
    
    /**
     * Get credentials from WooCommerce gateway settings
     */
    public static function get_gateway_credentials() {
        // Create gateway instance to access settings
        $gateway = new WC_Herepay_Payment_Gateway();
        
        return [
            'environment' => $gateway->environment ?: 'sandbox',
            'api_key' => $gateway->api_key ?: '',
            'secret_key' => $gateway->secret_key ?: '',
            'private_key' => $gateway->private_key ?: ''
        ];
    }
    
    /**
     * Get sandbox credentials - kept for backward compatibility
     * @deprecated Use get_gateway_credentials() instead
     */
    public static function get_sandbox_credentials() {
        // Return gateway credentials instead of hardcoded values
        return self::get_gateway_credentials();
    }
    
    /**
     * Apply gateway credentials to gateway for testing
     * No longer forces sandbox - uses actual configured environment
     */
    public static function apply_gateway_config($gateway) {
        $credentials = self::get_gateway_credentials();
        
        // Only override if credentials are actually configured
        if (!empty($credentials['api_key'])) {
            $gateway->api_key = $credentials['api_key'];
        }
        if (!empty($credentials['secret_key'])) {
            $gateway->secret_key = $credentials['secret_key'];
        }
        if (!empty($credentials['private_key'])) {
            $gateway->private_key = $credentials['private_key'];
        }
        if (!empty($credentials['environment'])) {
            $gateway->environment = $credentials['environment'];
        }
        
        return $gateway;
    }
    
    /**
     * Apply sandbox credentials to gateway for testing
     * @deprecated Use apply_gateway_config() instead for production readiness
     */
    public static function apply_sandbox_config($gateway) {
        return self::apply_gateway_config($gateway);
    }
    
    /**
     * Test API connection with gateway credentials
     */
    public static function test_connection() {
        $gateway = new WC_Herepay_Payment_Gateway();
        $gateway = self::apply_gateway_config($gateway);
        
        // Check if credentials are configured
        $credentials = self::get_gateway_credentials();
        if (empty($credentials['api_key']) || empty($credentials['secret_key'])) {
            return [
                'success' => false,
                'message' => 'API credentials not configured in WooCommerce settings',
                'debug' => 'Please configure API Key and Secret Key in WooCommerce > Settings > Payments > Herepay'
            ];
        }
        
        // Test payment channels API
        $channels = $gateway->getPaymentChannels();
        
        if ($channels && isset($channels['data'])) {
            return [
                'success' => true,
                'message' => 'API connection successful!',
                'channels_count' => count($channels['data']),
                'channels' => $channels['data'],
                'environment' => $credentials['environment']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to connect to Herepay API',
                'response' => $channels,
                'debug' => 'Check API credentials and network connectivity',
                'environment' => $credentials['environment']
            ];
        }
    }

    /**
     * Simple curl-based connection test using gateway credentials
     */
    public static function test_curl_connection() {
        $credentials = self::get_gateway_credentials();
        
        // Check if credentials are configured
        if (empty($credentials['api_key']) || empty($credentials['secret_key'])) {
            return [
                'success' => false,
                'message' => 'API credentials not configured in WooCommerce settings',
                'debug' => 'Please configure API Key and Secret Key in WooCommerce > Settings > Payments > Herepay'
            ];
        }
        
        // Determine URL based on environment
        $base_url = ($credentials['environment'] === 'production') ? 
            'https://app.herepay.org' : 'https://uat.herepay.org';
        $url = $base_url . '/api/v1/herepay/payment/channels';
        
        $headers = [
            'Content-Type: application/json',
            'XApiKey: ' . $credentials['api_key'],
            'SecretKey: ' . $credentials['secret_key']
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
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error,
                'http_code' => $http_code,
                'environment' => $credentials['environment']
            ];
        }
        
        $data = json_decode($response, true);
        
        if ($http_code === 200 && isset($data['data'])) {
            return [
                'success' => true,
                'message' => 'Direct cURL connection successful!',
                'channels_count' => count($data['data']),
                'http_code' => $http_code,
                'environment' => $credentials['environment']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API returned error: ' . ($data['message'] ?? 'Unknown error'),
                'http_code' => $http_code,
                'response' => $response,
                'environment' => $credentials['environment']
            ];
        }
    }
    
    /**
     * Test checksum generation using gateway credentials
     */
    public static function test_checksum() {
        $gateway = new WC_Herepay_Payment_Gateway();
        $gateway = self::apply_gateway_config($gateway);
        
        $credentials = self::get_gateway_credentials();
        if (empty($credentials['private_key'])) {
            return [
                'success' => false,
                'message' => 'Private key not configured',
                'debug' => 'Please configure Private Key in WooCommerce settings'
            ];
        }
        
        // Sample data for checksum testing
        $test_data = [
            'payment_code' => 'TEST-' . time(),
            'created_at' => date('Y-m-d H:i:s'),
            'amount' => 10.00,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'description' => 'Test payment',
            'bank_prefix' => 'TEST0021',
            'payment_method' => 'Online Banking'
        ];
        
        $checksum = $gateway->generateChecksum($test_data, $gateway->private_key);
        
        return [
            'success' => true,
            'test_data' => $test_data,
            'checksum' => $checksum,
            'checksum_length' => strlen($checksum),
            'environment' => $credentials['environment']
        ];
    }
    
    /**
     * Display test results in admin
     */
    public static function display_test_results() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        echo '<div class="herepay-test-results" style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3>🧪 Herepay Gateway Test Results</h3>';
        
        // Test API connection
        $connection_test = self::test_connection();
        echo '<div style="margin-bottom: 15px;">';
        echo '<h4>API Connection Test:</h4>';
        if ($connection_test['success']) {
            echo '<span style="color: green;">✅ SUCCESS</span> - ' . $connection_test['message'];
            echo '<br><small>Environment: ' . ($connection_test['environment'] ?: 'not set') . '</small>';
            echo '<br><small>Available payment channels: ' . $connection_test['channels_count'] . '</small>';
            
            if (!empty($connection_test['channels'])) {
                echo '<details style="margin-top: 10px;">';
                echo '<summary>View Available Payment Channels</summary>';
                echo '<ul style="margin: 10px 0; padding-left: 20px;">';
                foreach ($connection_test['channels'] as $channel) {
                    echo '<li><strong>' . esc_html($channel['bank_name'] ?? 'Unknown Bank') . '</strong>';
                    echo ' (' . esc_html($channel['payment_method'] ?? 'Unknown Method') . ')';
                    echo ' - Code: ' . esc_html($channel['bank_prefix'] ?? 'N/A') . '</li>';
                }
                echo '</ul>';
                echo '</details>';
            }
        } else {
            echo '<span style="color: red;">❌ FAILED</span> - ' . $connection_test['message'];
            if (isset($connection_test['debug'])) {
                echo '<br><small style="color: #666;">' . $connection_test['debug'] . '</small>';
            }
        }
        echo '</div>';
        
        // Test checksum generation
        $checksum_test = self::test_checksum();
        echo '<div style="margin-bottom: 15px;">';
        echo '<h4>Checksum Generation Test:</h4>';
        if ($checksum_test['success']) {
            echo '<span style="color: green;">✅ SUCCESS</span> - Checksum generated successfully';
            echo '<br><small>Checksum: <code>' . substr($checksum_test['checksum'], 0, 20) . '...</code> (Length: ' . $checksum_test['checksum_length'] . ' chars)</small>';
        } else {
            echo '<span style="color: red;">❌ FAILED</span> - ' . $checksum_test['message'];
            if (isset($checksum_test['debug'])) {
                echo '<br><small style="color: #666;">' . $checksum_test['debug'] . '</small>';
            }
        }
        echo '</div>';
        
        // Display credentials info
        echo '<div style="margin-bottom: 15px;">';
        echo '<h4>Gateway Credentials Status:</h4>';
        $credentials = self::get_gateway_credentials();
        
        $api_configured = !empty($credentials['api_key']);
        $secret_configured = !empty($credentials['secret_key']);
        $private_configured = !empty($credentials['private_key']);
        
        if ($api_configured && $secret_configured && $private_configured) {
            echo '<span style="color: green;">✅ FULLY CONFIGURED</span> - All credentials are properly set';
            echo '<br><small>Environment: ' . esc_html($credentials['environment'] ?: 'not set') . '</small>';
            echo '<br><small>API Key: ' . substr($credentials['api_key'], 0, 20) . '...</small>';
        } else {
            echo '<span style="color: orange;">⚠️ INCOMPLETE</span> - Some credentials are missing';
            echo '<br><small>API Key: ' . ($api_configured ? '✅ Set' : '❌ Missing') . '</small>';
            echo '<br><small>Secret Key: ' . ($secret_configured ? '✅ Set' : '❌ Missing') . '</small>';
            echo '<br><small>Private Key: ' . ($private_configured ? '✅ Set' : '❌ Missing') . '</small>';
            echo '<br><small style="color: #666;">Configure missing credentials in WooCommerce > Settings > Payments > Herepay</small>';
        }
        echo '</div>';
        
        echo '<div style="background: #e7f3ff; padding: 10px; border-left: 4px solid #007cba; margin-top: 15px;">';
        echo '<strong>💡 Note:</strong> This test uses the actual credentials configured in WooCommerce settings. ';
        echo 'Test results will vary based on your configured environment (sandbox/production).';
        echo '<br><strong>✅ API Headers:</strong> Using correct XApiKey and SecretKey headers.';
        echo '</div>';
        
        echo '</div>';
    }
}
