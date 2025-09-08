<?php

if (!defined('ABSPATH')) {
    exit;
}

class Herepay_Payment_Form {
    
    public static function init() {
        add_action('wp', [__CLASS__, 'handle_payment_form_request']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }
    
    public static function handle_payment_form_request() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public payment form display, no state changes
        if (isset($_GET['herepay_payment']) && $_GET['herepay_payment'] === 'form') {
            self::display_payment_form();
            exit;
        }
    }
    
    public static function enqueue_scripts() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking for payment form display, no state changes
        if (is_checkout() || (isset($_GET['herepay_payment']) && $_GET['herepay_payment'] === 'form')) {
            wp_enqueue_script('jquery');
        }
    }
    
    public static function display_payment_form() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Payment form display, order_id from payment flow
        if (!isset($_GET['order_id'])) {
            wp_die('Invalid payment request');
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Payment form display, order_id from payment flow
        $payment_code = sanitize_text_field(wp_unslash($_GET['order_id']));
        
        // Find order by payment code
        $orders = wc_get_orders([
            'meta_key' => '_herepay_payment_code',
            'meta_value' => $payment_code,
            'limit' => 1
        ]);
        
        if (empty($orders)) {
            wp_die('Order not found');
        }
        
        $order = $orders[0];
        $gateway = new WC_Herepay_Payment_Gateway();
        
        // Get payment data from order meta
        $bank_prefix = $order->get_meta('_herepay_bank_prefix');
        $payment_method = $order->get_meta('_herepay_payment_method');
        
        // Prepare payment data for Herepay API (matching your Node.js example)
        $data = [
            'payment_code' => $payment_code,
            'created_at' => current_time('Y-m-d H:i:s'),
            'amount' => $order->get_total(),
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone() ?: '0123456789',
            'description' => 'Order #' . $order->get_id() . ' from ' . get_bloginfo('name'),
            'bank_prefix' => $bank_prefix === 'TEST0021' ? 'TEST0021' : $bank_prefix,
            'payment_method' => $payment_method === 'Online Banking' ? 'Online Banking' : $payment_method,
            'redirect_url' => home_url('/herepay-redirect')
        ];
        
        // Don't generate checksum here - let the WordPress handler do it
        // This avoids potential issues with the generateChecksum method
        
        $api_url = $gateway->getEnvironment() . '/api/v1/herepay/initiate';
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e('Processing Payment...', 'herepay-for-woocommerce'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f1f1f1;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .payment-container {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                    text-align: center;
                    max-width: 500px;
                    width: 100%;
                }
                .herepay-logo {
                    max-height: 40px;
                    margin-bottom: 20px;
                }
                .spinner {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #030657;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 2s linear infinite;
                    margin: 20px auto;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .payment-info {
                    margin: 20px 0;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                    text-align: left;
                }
                .payment-info h3 {
                    margin-top: 0;
                    color: #333;
                    text-align: center;
                }
                .payment-detail {
                    display: flex;
                    justify-content: space-between;
                    margin: 10px 0;
                    padding: 5px 0;
                    border-bottom: 1px solid #eee;
                }
                .payment-detail:last-child {
                    border-bottom: none;
                    font-weight: bold;
                    font-size: 1.1em;
                }
                .continue-btn {
                    background: #030657;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                    margin-top: 20px;
                    transition: background-color 0.3s;
                }
                .continue-btn:hover {
                    background: #020546;
                }
            </style>
        </head>
        <body>
            <div class="payment-container">
                <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/herepay-logo.png'); ?>" alt="Herepay" class="herepay-logo">
                <h2><?php esc_html_e('Processing Your Payment', 'herepay-for-woocommerce'); ?></h2>
                <div class="spinner"></div>
                <p><?php esc_html_e('Please wait while we redirect you to the payment gateway...', 'herepay-for-woocommerce'); ?></p>
                
                <div class="payment-info">
                    <h3><?php esc_html_e('Payment Details', 'herepay-for-woocommerce'); ?></h3>
                    <div class="payment-detail">
                        <span><?php esc_html_e('Order ID:', 'herepay-for-woocommerce'); ?></span>
                        <span><?php echo esc_html($order->get_id()); ?></span>
                    </div>
                    <div class="payment-detail">
                        <span><?php esc_html_e('Payment Method:', 'herepay-for-woocommerce'); ?></span>
                        <span><?php echo esc_html($payment_method); ?></span>
                    </div>
                    <div class="payment-detail">
                        <span><?php esc_html_e('Bank:', 'herepay-for-woocommerce'); ?></span>
                        <span><?php echo esc_html($bank_prefix); ?></span>
                    </div>
                    <div class="payment-detail">
                        <span><?php esc_html_e('Amount:', 'herepay-for-woocommerce'); ?></span>
                        <span><?php echo wp_kses_post(wc_price($order->get_total())); ?></span>
                    </div>
                </div>
                
                                <!-- Form POSTs to WordPress admin-post.php handler -->
                <form id="herepay-payment-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
                    <input type="hidden" name="action" value="herepay_process">
                    <?php wp_nonce_field('herepay_process_payment', 'herepay_nonce'); ?>
                    <?php 
                    foreach ($data as $key => $value): ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                    
                    <!-- Debug: Show form action URL -->
                    <script>
                        console.log('Form action URL:', '<?php echo esc_js(admin_url('admin-post.php')); ?>');
                        console.log('Form data count:', <?php echo count($data); ?>);
                    </script>
                </form>
                
                <script>
                    // Debug form submission
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('herepay-payment-form');
                        console.log('Form found:', form);
                        console.log('Form action:', form.action);
                        console.log('Form method:', form.method);
                        console.log('Form elements count:', form.elements.length);
                        
                        // Log all form data
                        for (let i = 0; i < form.elements.length; i++) {
                            const element = form.elements[i];
                            console.log('Form field:', element.name, '=', element.value);
                        }
                    });
                    
                    // Auto-submit the form after 3 seconds
                    setTimeout(function() {
                        console.log('Auto-submitting form...');
                        document.getElementById('herepay-payment-form').submit();
                    }, 3000);
                </script>
                
                <p style="margin-top: 30px; font-size: 14px; color: #666;">
                    <?php esc_html_e('If you are not redirected automatically, please click the button below.', 'herepay-for-woocommerce'); ?>
                </p>
                <button type="button" onclick="document.getElementById('herepay-payment-form').submit();" class="continue-btn">
                    <?php esc_html_e('Continue to Payment', 'herepay-for-woocommerce'); ?>
                </button>
            </div>
        </body>
        </html>
        <?php
    }
}
