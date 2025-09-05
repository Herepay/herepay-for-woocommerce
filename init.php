<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Herepay_Payment_Gateway extends WC_Payment_Gateway {
    public $api_key;
    public $secret_key;
    public $private_key;
    public $environment;
    public $redirect_url;
    public $callback_url;

    public function __construct() {
        $this->id = 'herepay_payment_gateway';
        $this->icon = plugin_dir_url(dirname(__FILE__)) . 'assets/images/herepay-logo.png';
        $this->method_title = __('Herepay Payment Gateway', 'herepay-wc');
        $this->method_description = __('Herepay Payment Gateway integration for secure online payments.', 'herepay-wc');
        $this->supports = ['products'];
        $this->has_fields = true;

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Assign settings values
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title') ?: __('Herepay Payment Gateway', 'herepay-wc');
        $this->description = $this->get_option('description') ?: __('Pay securely using Herepay Payment Gateway.', 'herepay-wc');
        $this->api_key = $this->get_option('api_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->private_key = $this->get_option('private_key');
        $this->environment = $this->get_option('environment');
        $this->redirect_url = $this->get_option('redirect_url');
        $this->callback_url = $this->get_option('callback_url');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_' . strtolower(get_class($this)), [$this, 'handle_callback']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'herepay-wc'),
                'type' => 'checkbox',
                'label' => __('Enable Herepay Payment Gateway', 'herepay-wc'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Title', 'herepay-wc'),
                'type' => 'text',
                'description' => __('Payment method title shown at checkout.', 'herepay-wc'),
                'default' => __('Herepay Payment Gateway', 'herepay-wc')
            ],
            'description' => [
                'title' => __('Description', 'herepay-wc'),
                'type' => 'textarea',
                'description' => __('Description shown to customers at checkout.', 'herepay-wc'),
                'default' => __('Pay securely using Herepay Payment Gateway.', 'herepay-wc')
            ],
            'environment' => [
                'title' => __('Environment', 'herepay-wc'),
                'type' => 'select',
                'description' => __('Select the environment for your Herepay account.', 'herepay-wc'),
                'default' => 'sandbox',
                'options' => [
                    'sandbox' => __('Sandbox', 'herepay-wc'),
                    'production' => __('Production', 'herepay-wc')
                ]
            ],
            'api_key' => [
                'title' => __('API Key', 'herepay-wc'),
                'type' => 'text',
                'description' => __('Enter your Herepay API key.', 'herepay-wc'),
                'default' => ''
            ],
            'secret_key' => [
                'title' => __('Secret Key', 'herepay-wc'),
                'type' => 'password',
                'description' => __('Enter your Herepay Secret Key.', 'herepay-wc'),
                'default' => ''
            ],
            'private_key' => [
                'title' => __('Private Key', 'herepay-wc'),
                'type' => 'password',
                'description' => __('Enter your Herepay Private Key (used for checksum generation).', 'herepay-wc'),
                'default' => ''
            ],
            'redirect_url' => [
                'title' => __('Redirect URL', 'herepay-wc'),
                'type' => 'text',
                'description' => sprintf(__('URL to redirect customers after payment completion. Use: %s', 'herepay-wc'), home_url('/herepay-redirect')),
                'default' => home_url('/herepay-redirect'),
                'custom_attributes' => ['readonly' => 'readonly']
            ],
            'callback_url' => [
                'title' => __('Callback URL', 'herepay-wc'),
                'type' => 'text',
                'description' => sprintf(__('Webhook URL for payment notifications. Use: %s', 'herepay-wc'), home_url('/wc-api/wc_herepay_payment_gateway')),
                'default' => home_url('/wc-api/wc_herepay_payment_gateway'),
                'custom_attributes' => ['readonly' => 'readonly']
            ]
        ];
    }

    /**
     * Check if the gateway is available for use
     */
    public function is_available() {
        $is_available = ('yes' === $this->enabled);
        
        if (!$is_available) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        return $is_available;
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        echo '<h3>' . __('Herepay Payment Gateway', 'herepay-wc') . '</h3>';
        echo '<p>' . __('Configure your Herepay payment gateway settings below.', 'herepay-wc') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function getEnvironment() {
        if ($this->environment === 'sandbox') {
            return 'https://uat.herepay.org';
        } else {
            return 'https://app.herepay.org';
        }
    }

    /**
     * Generate checksum for Herepay API (matching Node.js implementation)
     */
    public function generateChecksum($data, $privateKey) {
        // Sort the data keys
        ksort($data);
        
        // Convert arrays/objects to JSON strings
        $processedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $processedData[$key] = json_encode($value);
            } else {
                $processedData[$key] = $value;
            }
        }
        
        // Concatenate all values with commas (matching Node.js implementation)
        $concatenatedData = implode(',', array_values($processedData));
        
        // Generate HMAC-SHA256
        return hash_hmac('sha256', $concatenatedData, $privateKey);
    }

    /**
     * Get payment channels from Herepay API
     */
    public function getPaymentChannels() {
        $url = $this->getEnvironment() . '/api/v1/herepay/payment/channels';
        
        $headers = [
            'Content-Type: application/json',
            'XApiKey: ' . $this->api_key,
            'SecretKey: ' . $this->secret_key
        ];

        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'XApiKey' => $this->api_key,
                'SecretKey' => $this->secret_key
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Get payment channels with specific credentials
     */
    public function getPaymentChannelsWithCredentials($api_key, $secret_key) {
        $url = $this->getEnvironment() . '/api/v1/herepay/payment/channels';
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'XApiKey' => $api_key,
                'SecretKey' => $secret_key
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Display payment form on checkout
     */
    public function payment_fields() {
        // Display description
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description) . '</p>';
        }

        // Check if credentials are set, if not use sandbox for testing
        $api_key = $this->api_key;
        $secret_key = $this->secret_key;
        
        if (empty($api_key) || empty($secret_key)) {
            // Use sandbox credentials for testing
            if (class_exists('Herepay_Test_Config')) {
                $sandbox_creds = Herepay_Test_Config::get_sandbox_credentials();
                $api_key = $sandbox_creds['api_key'];
                $secret_key = $sandbox_creds['secret_key'];
            } else {
                echo '<div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">';
                echo '<p style="margin: 0; color: #856404;"><strong>' . __('Payment gateway is not fully configured.', 'herepay-wc') . '</strong></p>';
                echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #856404;">' . __('Please contact the store administrator to configure Herepay payment settings.', 'herepay-wc') . '</p>';
                echo '</div>';
                return;
            }
        }

        // Get payment channels using the correct credentials
        $channels = $this->getPaymentChannelsWithCredentials($api_key, $secret_key);
        
        if (!$channels || !isset($channels['data']) || empty($channels['data'])) {
            echo '<div style="padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<p style="margin: 0; color: #721c24;"><strong>' . __('Unable to load payment channels.', 'herepay-wc') . '</strong></p>';
            echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #721c24;">' . __('Please try again later or contact support if the problem persists.', 'herepay-wc') . '</p>';
            echo '</div>';
            return;
        }

        echo '<div id="herepay-payment-form" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background-color: #f9f9f9; margin-top: 10px;">';
        echo '<label for="herepay_bank_prefix" style="display: block; margin-bottom: 5px; font-weight: bold;">' . __('Select Bank', 'herepay-wc') . ' <span class="required" style="color: red;">*</span></label>';
        echo '<select id="herepay_bank_prefix" name="herepay_bank_prefix" required style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
        echo '<option value="">' . __('Select a bank...', 'herepay-wc') . '</option>';
        
        // Parse the channels data structure properly
        foreach ($channels['data'] as $method_group) {
            if (isset($method_group['channels']) && is_array($method_group['channels'])) {
                $payment_method = $method_group['payment_method'] ?? 'Online Banking';
                
                foreach ($method_group['channels'] as $channel) {
                    if (isset($channel['prefix']) && isset($channel['name']) && $channel['active'] === 'Active') {
                        $bank_prefix = $channel['prefix'];
                        $bank_name = !empty($channel['name']) ? $channel['name'] : $bank_prefix;
                        
                        echo '<option value="' . esc_attr($bank_prefix) . '" data-method="' . esc_attr($payment_method) . '">';
                        echo esc_html($bank_name) . ' (' . esc_html($payment_method) . ')';
                        echo '</option>';
                    }
                }
            }
        }
        
        echo '</select>';
        echo '<input type="hidden" id="herepay_payment_method" name="herepay_payment_method" value="" />';
        echo '</div>';

        // Add JavaScript for dynamic payment method selection
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#herepay_bank_prefix').change(function() {
                var selectedOption = $(this).find('option:selected');
                var paymentMethod = selectedOption.data('method');
                $('#herepay_payment_method').val(paymentMethod);
                
                // Add visual feedback
                $('.herepay-selected-method').remove();
                if (paymentMethod) {
                    $(this).after('<div class="herepay-selected-method" style="margin-top: 5px; font-size: 12px; color: #666;">✓ Payment Method: ' + paymentMethod + '</div>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Validate payment form fields
     * Handles both classic and block checkout validation
     */
    public function validate_fields() {
        // Get bank prefix from either classic form or block checkout
        $bank_prefix = $this->get_payment_post_data('herepay_bank_prefix');
        $payment_method = $this->get_payment_post_data('herepay_payment_method');
        
        if (empty($bank_prefix)) {
            wc_add_notice(__('Please select a bank for payment.', 'herepay-wc'), 'error');
            return false;
        }
        
        if (empty($payment_method)) {
            wc_add_notice(__('Payment method not selected.', 'herepay-wc'), 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Get payment POST data with fallback for block checkout
     * Block checkout sends data differently than classic checkout
     */
    public function get_payment_post_data($key) {
        // First try classic checkout $_POST
        if (isset($_POST[$key]) && !empty($_POST[$key])) {
            return sanitize_text_field($_POST[$key]);
        }
        
        // Try block checkout format - data might be in payment_data array
        if (isset($_POST['payment_data']) && is_array($_POST['payment_data'])) {
            foreach ($_POST['payment_data'] as $data) {
                if (isset($data['key']) && $data['key'] === $key && !empty($data['value'])) {
                    return sanitize_text_field($data['value']);
                }
            }
        }
        
        // Try direct key in case block sends it differently
        if (isset($_POST['payment_method_data']) && isset($_POST['payment_method_data'][$key])) {
            return sanitize_text_field($_POST['payment_method_data'][$key]);
        }
        
        return '';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wc_add_notice(__('Order not found.', 'herepay-wc'), 'error');
            return ['result' => 'fail'];
        }

        // Get form data (works for both classic and block checkout)
        $bank_prefix = $this->get_payment_post_data('herepay_bank_prefix');
        $payment_method = $this->get_payment_post_data('herepay_payment_method');

        if (empty($bank_prefix) || empty($payment_method)) {
            wc_add_notice(__('Please select a bank for payment.', 'herepay-wc'), 'error');
            return ['result' => 'fail'];
        }

        // Prepare payment data matching Node.js structure
        $payment_code = 'PAY' . rand(100000, 999999); // Generate 6-digit code with PAY prefix like Node.js
        $created_at = current_time('Y-m-d H:i:s');
        
        $data = [
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'payment_code' => $payment_code,
            'created_at' => $created_at,
            'amount' => $order->get_total(),
            'phone' => $order->get_billing_phone() ?: '0123456789', // Default phone if empty
            'description' => 'Order #' . $order_id . ' from ' . get_bloginfo('name'),
            'bank_prefix' => $bank_prefix,
            'payment_method' => $payment_method
        ];

        // Store payment reference in order meta
        $order->update_meta_data('_herepay_payment_code', $payment_code);
        $order->update_meta_data('_herepay_bank_prefix', $bank_prefix);
        $order->update_meta_data('_herepay_payment_method', $payment_method);
        $order->save();

        // Update order status
        $order->update_status('pending', __('Awaiting Herepay payment confirmation.', 'herepay-wc'));

        // Instead of calling API directly, redirect to payment form
        $payment_form_url = add_query_arg([
            'herepay_payment' => 'form',
            'order_id' => $payment_code
        ], home_url());

        return [
            'result' => 'success',
            'redirect' => $payment_form_url
        ];
    }

    /**
     * Get payment form URL for manual form submission
     */
    private function getPaymentFormUrl($data) {
        // Create a temporary page/endpoint to display the payment form
        return add_query_arg([
            'herepay_payment' => 'form',
            'order_id' => $data['payment_code']
        ], home_url());
    }

    /**
     * Handle webhook/callback from Herepay
     * Based on https://herepay.readme.io/reference/post_callback
     */
    public function handle_callback() {
        // Get raw POST data
        $raw_body = file_get_contents('php://input');
        
        // Log the raw callback for debugging
        error_log('Herepay Callback Raw Data: ' . $raw_body);
        
        // Try to parse as JSON first
        $callback_data = json_decode($raw_body, true);
        
        // If JSON parsing failed, try to parse as form data
        if (json_last_error() !== JSON_ERROR_NONE || !$callback_data) {
            parse_str($raw_body, $callback_data);
        }
        
        // Log parsed data
        error_log('Herepay Callback Parsed Data: ' . wp_json_encode($callback_data));

        // Validate required fields
        if (!$callback_data || !isset($callback_data['payment_code'])) {
            error_log('Herepay Callback Error: Missing payment_code');
            wp_die('Invalid callback data - missing payment_code', 'Herepay Callback', ['response' => 400]);
        }

        $payment_code = sanitize_text_field($callback_data['payment_code']);
        
        // Find order by payment code - HPOS compatible
        $orders = wc_get_orders([
            'meta_key' => '_herepay_payment_code',
            'meta_value' => $payment_code,
            'limit' => 1,
            'status' => 'any'
        ]);

        if (empty($orders)) {
            error_log('Herepay Callback Error: Order not found for payment_code: ' . $payment_code);
            wp_die('Order not found', 'Herepay Callback', ['response' => 404]);
        }

        $order = $orders[0];
        
        // Verify callback authenticity using checksum if available
        if (isset($callback_data['checksum']) && !empty($this->private_key)) {
            $received_checksum = $callback_data['checksum'];
            
            // Create data array without checksum for verification
            $verify_data = $callback_data;
            unset($verify_data['checksum']);
            
            // Generate checksum to verify
            $calculated_checksum = $this->generateChecksum($verify_data, $this->private_key);
            
            if ($received_checksum !== $calculated_checksum) {
                error_log('Herepay Callback Error: Invalid checksum');
                $order->add_order_note(__('Herepay callback received with invalid checksum - possible security issue.', 'herepay-wc'));
                wp_die('Invalid checksum', 'Herepay Callback', ['response' => 403]);
            }
        }
        
        // Get payment status from callback
        $payment_status = isset($callback_data['status']) ? sanitize_text_field($callback_data['status']) : '';
        $status_code = isset($callback_data['status_code']) ? sanitize_text_field($callback_data['status_code']) : '';
        $transaction_id = isset($callback_data['transaction_id']) ? sanitize_text_field($callback_data['transaction_id']) : '';
        $amount = isset($callback_data['amount']) ? floatval($callback_data['amount']) : 0;
        $message = isset($callback_data['message']) ? sanitize_text_field($callback_data['message']) : '';
        
        // Log status update
        error_log("Herepay Callback: Order {$order->get_id()}, Payment Code: {$payment_code}, Status: {$payment_status}, Status Code: {$status_code}, Message: {$message}");
        
        // Handle both status and status_code for comprehensive coverage
        $is_success = (
            $status_code === '00' || 
            strtolower($payment_status) === 'success' || 
            strtolower($payment_status) === 'completed' ||
            strtolower($message) === 'approved'
        );
        
        if ($is_success) {
            // Verify amount if provided
            if ($amount > 0 && abs($order->get_total() - $amount) > 0.01) {
                $order->add_order_note(sprintf(
                    __('Herepay payment amount mismatch. Expected: %s, Received: %s', 'herepay-wc'),
                    $order->get_total(),
                    $amount
                ));
                error_log("Herepay Callback: Amount mismatch for order {$order->get_id()}");
            }
            
            // Mark payment as complete
            if ($transaction_id) {
                $order->set_transaction_id($transaction_id);
            }
            
            // Update to processing status first, then complete payment
            $order->update_status('processing', __('Payment received via Herepay - processing order.', 'herepay-wc'));
            $order->payment_complete();
            $order->add_order_note(__('Payment completed successfully via Herepay callback.', 'herepay-wc'));
            
            // Reduce stock
            wc_reduce_stock_levels($order->get_id());
            
            error_log("Herepay Callback: Order {$order->get_id()} status updated to: " . $order->get_status());
        } else {
            // Update order based on other Herepay status codes
            switch ($payment_status) {
                case '30': // Failed
            case 'failed':
            case 'cancelled':
                $order->update_status('failed', __('Payment failed via Herepay.', 'herepay-wc'));
                
                // Restore stock if it was reduced
                if ($order->get_data_store()->get_stock_reduced($order->get_id())) {
                    wc_increase_stock_levels($order);
                }
                break;
                
            case '29': // Pending
            case 'pending':
            case 'processing':
                $order->update_status('pending', __('Payment is pending confirmation via Herepay.', 'herepay-wc'));
                break;
                
            case '41': // Unauthorized
            case 'unauthorized':
                $order->update_status('failed', __('Payment authorization failed via Herepay.', 'herepay-wc'));
                break;
                
            default:
                // Unknown status - log but don't change order status
                $order->add_order_note(sprintf(
                    __('Herepay callback received with unknown status: %s', 'herepay-wc'),
                    $payment_status
                ));
                error_log("Herepay Callback: Unknown status '{$payment_status}' for order {$order->get_id()}");
                break;
        }
        }

        // Add comprehensive order note with all callback data
        $note_data = [
            'payment_code' => $payment_code,
            'status' => $payment_status,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'timestamp' => current_time('Y-m-d H:i:s')
        ];
        
        $order->add_order_note('Herepay callback: ' . wp_json_encode($note_data));
        
        // Save order
        $order->save();
        
        // Return success response to Herepay
        http_response_code(200);
        echo 'OK';
        exit;
    }

    /**
     * Add custom rewrite rules for Herepay redirect
     */
    public function add_herepay_rewrite_rules() {
        add_rewrite_rule('^herepay-redirect/?', 'index.php?herepay_redirect=1', 'top');
        
        // Flush rewrite rules if they haven't been flushed yet
        if (get_option('herepay_rewrite_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('herepay_rewrite_rules_flushed', '1');
        }
    }

    /**
     * Add custom query vars
     */
    public function add_herepay_query_vars($vars) {
        $vars[] = 'herepay_redirect';
        return $vars;
    }

    /**
     * Handle Herepay redirect URL - /herepay-redirect
     * Processes payment completion data similar to callback
     */
    public function handle_herepay_redirect() {
        // Check if this is a Herepay redirect request
        if (!get_query_var('herepay_redirect')) {
            return;
        }
        
        // Get data from both GET and POST
        $redirect_data = array_merge($_GET, $_POST);
        
        // Log the redirect data for debugging
        error_log('Herepay Redirect Data: ' . wp_json_encode($redirect_data));
        error_log('Herepay Redirect URL accessed at: ' . current_time('Y-m-d H:i:s'));
        
        // Validate required fields
        if (!isset($redirect_data['payment_code']) || empty($redirect_data['payment_code'])) {
            // Log missing payment code
            error_log('Herepay Redirect Error: Missing payment_code in redirect data');
            
            // Redirect to cart with error if no payment code
            wc_add_notice(__('Invalid payment data received.', 'herepay-wc'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        }
        
        $payment_code = sanitize_text_field($redirect_data['payment_code']);
        
        // Find order by payment code
        $orders = wc_get_orders([
            'meta_key' => '_herepay_payment_code',
            'meta_value' => $payment_code,
            'limit' => 1,
            'status' => 'any'
        ]);
        
        if (empty($orders)) {
            // Redirect to cart with error if order not found
            wc_add_notice(__('Order not found.', 'herepay-wc'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        }
        
        $order = $orders[0];
        
        // Get payment status from redirect data
        $payment_status = isset($redirect_data['status']) ? sanitize_text_field($redirect_data['status']) : '';
        $status_code = isset($redirect_data['status_code']) ? sanitize_text_field($redirect_data['status_code']) : '';
        $transaction_id = isset($redirect_data['transaction_id']) ? sanitize_text_field($redirect_data['transaction_id']) : '';
        $amount = isset($redirect_data['amount']) ? floatval($redirect_data['amount']) : 0;
        $message = isset($redirect_data['message']) ? sanitize_text_field($redirect_data['message']) : '';
        
        // Log the status information for debugging
        error_log("Herepay Redirect: Order {$order->get_id()}, Payment Code: {$payment_code}, Status: {$payment_status}, Status Code: {$status_code}, Message: {$message}");
        
        // Verify checksum if available and private key is set
        if (isset($redirect_data['checksum']) && !empty($this->private_key)) {
            $received_checksum = $redirect_data['checksum'];
            
            // Create data array without checksum for verification
            $verify_data = $redirect_data;
            unset($verify_data['checksum']);
            
            // Generate checksum to verify
            $calculated_checksum = $this->generateChecksum($verify_data, $this->private_key);
            
            if ($received_checksum !== $calculated_checksum) {
                error_log('Herepay Redirect Error: Invalid checksum for order ' . $order->get_id());
                $order->add_order_note(__('Herepay redirect received with invalid checksum.', 'herepay-wc'));
                
                // Redirect to order pay page with error
                wc_add_notice(__('Payment verification failed. Please try again.', 'herepay-wc'), 'error');
                wp_redirect($order->get_checkout_payment_url());
                exit;
            }
        }
        
        // Add redirect note to order
        $redirect_note_data = [
            'payment_code' => $payment_code,
            'status' => $payment_status,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'timestamp' => current_time('Y-m-d H:i:s'),
            'source' => 'redirect'
        ];
        $order->add_order_note('Herepay redirect: ' . wp_json_encode($redirect_note_data));
        
        // Process payment status and redirect accordingly
        // Handle both status and status_code for comprehensive coverage
        $is_success = (
            $status_code === '00' || 
            strtolower($payment_status) === 'success' || 
            strtolower($payment_status) === 'completed' ||
            strtolower($message) === 'approved'
        );
        
        if ($is_success) {
            // Log successful payment processing
            error_log("Herepay Redirect: Processing successful payment for order {$order->get_id()}, status: {$payment_status}, status_code: {$status_code}");
            
            // If not already completed, mark as complete
            if (!$order->is_paid()) {
                if ($transaction_id) {
                    $order->set_transaction_id($transaction_id);
                }
                
                // For successful payments, update to processing status first, then complete
                $order->update_status('processing', __('Payment received via Herepay - processing order.', 'herepay-wc'));
                
                // Then mark payment as complete (this will change status to completed for virtual/downloadable products)
                $order->payment_complete();
                $order->add_order_note(__('Payment completed successfully via Herepay redirect.', 'herepay-wc'));
                
                // Reduce stock
                wc_reduce_stock_levels($order->get_id());
                
                error_log("Herepay Redirect: Order {$order->get_id()} status updated to: " . $order->get_status());
            } else {
                error_log("Herepay Redirect: Order {$order->get_id()} already paid, current status: " . $order->get_status());
            }
            
            // Clear cart
            WC()->cart->empty_cart();
            
            // Redirect to thank you page
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        }
        
        switch ($payment_status) {
            case '00': // Success
            case 'success':
            case 'completed':
                // Log successful payment processing
                error_log("Herepay Redirect: Processing successful payment for order {$order->get_id()}, status: {$payment_status}");
                
                // If not already completed, mark as complete
                if (!$order->is_paid()) {
                    if ($transaction_id) {
                        $order->set_transaction_id($transaction_id);
                    }
                    
                    // For successful payments, update to processing status first, then complete
                    $order->update_status('processing', __('Payment received via Herepay - processing order.', 'herepay-wc'));
                    
                    // Then mark payment as complete (this will change status to completed for virtual/downloadable products)
                    $order->payment_complete();
                    $order->add_order_note(__('Payment completed successfully via Herepay redirect.', 'herepay-wc'));
                    
                    // Reduce stock
                    wc_reduce_stock_levels($order->get_id());
                    
                    error_log("Herepay Redirect: Order {$order->get_id()} status updated to: " . $order->get_status());
                } else {
                    error_log("Herepay Redirect: Order {$order->get_id()} already paid, current status: " . $order->get_status());
                }
                
                // Clear cart
                WC()->cart->empty_cart();
                
                // Redirect to thank you page
                wp_redirect($order->get_checkout_order_received_url());
                exit;
                
            case '30': // Failed
            case 'failed':
            case 'cancelled':
                $order->update_status('failed', __('Payment failed via Herepay redirect.', 'herepay-wc'));
                
                // Add error notice and redirect to checkout
                wc_add_notice(__('Payment was not successful. Please try again.', 'herepay-wc'), 'error');
                wp_redirect($order->get_checkout_payment_url());
                exit;
                
            case '29': // Pending
            case 'pending':
            case 'processing':
                $order->update_status('pending', __('Payment is pending confirmation via Herepay redirect.', 'herepay-wc'));
                
                // Redirect to order received page with pending message
                wc_add_notice(__('Your payment is being processed. You will receive confirmation once completed.', 'herepay-wc'), 'notice');
                wp_redirect($order->get_checkout_order_received_url());
                exit;
                
            case '41': // Unauthorized
            case 'unauthorized':
                $order->update_status('failed', __('Payment authorization failed via Herepay redirect.', 'herepay-wc'));
                
                // Add error notice and redirect to checkout
                wc_add_notice(__('Payment authorization failed. Please try again.', 'herepay-wc'), 'error');
                wp_redirect($order->get_checkout_payment_url());
                exit;
                
            default:
                // Unknown status - redirect to order details with notice
                $order->add_order_note(sprintf(
                    __('Herepay redirect received with unknown status: %s', 'herepay-wc'),
                    $payment_status
                ));
                
                wc_add_notice(__('Payment status is unclear. Please contact support if you have completed the payment.', 'herepay-wc'), 'notice');
                wp_redirect($order->get_view_order_url());
                exit;
        }
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus($payment_code) {
        $url = $this->getEnvironment() . '/api/v1/herepay/transactions/' . $payment_code;
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'XApiKey' => $this->api_key,
                'SecretKey' => $this->secret_key
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
?>
