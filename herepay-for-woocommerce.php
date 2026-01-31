<?php
/**
 * Plugin Name: Herepay Payment Gateway for WooCommerce
 * Description: Herepay Payment Gateway for WooCommerce - Accept secure online payments through Herepay.
 * Version: 1.0.2
 * Author: Herepay
 * Author URI: https://herepay.org
 * Text Domain: herepay-for-woocommerce
 * Requires Plugins: woocommerce
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC requires at least: 5.0
 * WC tested up to: 9.6.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HEREPAY_FOR_WOOCOMMERCE_VERSION', '1.0.2');
define('HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH', plugin_dir_path(__FILE__));

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'herepay_woocommerce_missing_notice');
    return;
}

function herepay_woocommerce_missing_notice()
{
    echo '<div class="notice notice-error"><p>';
    echo esc_html__('Herepay Payment Gateway requires WooCommerce to be installed and active.', 'herepay-for-woocommerce');
    echo '</p></div>';
}

function herepay_payment_gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    include_once HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH . 'init.php';
    include_once HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-herepay-payment-form.php';
    include_once HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-herepay-admin.php';
    include_once HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-herepay-blocks-integration.php';

    Herepay_Payment_Form::init();
    Herepay_Admin::init();

    add_action('admin_post_nopriv_herepay_process', 'herepay_handle_payment_processing');
    add_action('admin_post_herepay_process', 'herepay_handle_payment_processing');
}
add_action('plugins_loaded', 'herepay_payment_gateway_init');

/**
 * Define allowed HTML tags for Herepay payment response
 * Includes script tags to allow redirect functionality
 */
function herepay_get_allowed_html()
{
    return array(
        'script' => array(
            'type' => array(),
            'src' => array(),
            'charset' => array(),
            'async' => array(),
            'defer' => array()
        ),
        'form' => array(
            'action' => array(),
            'method' => array(),
            'name' => array(),
            'id' => array(),
            'class' => array(),
            'target' => array(),
            'enctype' => array()
        ),
        'input' => array(
            'type' => array(),
            'name' => array(),
            'value' => array(),
            'id' => array(),
            'class' => array(),
            'hidden' => array(),
            'readonly' => array(),
            'disabled' => array()
        ),
        'button' => array(
            'type' => array(),
            'name' => array(),
            'value' => array(),
            'id' => array(),
            'class' => array(),
            'onclick' => array()
        ),
        'div' => array(
            'id' => array(),
            'class' => array(),
            'style' => array()
        ),
        'span' => array(
            'id' => array(),
            'class' => array(),
            'style' => array()
        ),
        'p' => array(
            'id' => array(),
            'class' => array(),
            'style' => array()
        ),
        'a' => array(
            'href' => array(),
            'target' => array(),
            'id' => array(),
            'class' => array(),
            'onclick' => array()
        ),
        'img' => array(
            'src' => array(),
            'alt' => array(),
            'width' => array(),
            'height' => array(),
            'id' => array(),
            'class' => array()
        ),
        'br' => array(),
        'hr' => array(),
        'strong' => array(),
        'em' => array(),
        'b' => array(),
        'i' => array(),
        'u' => array(),
        'h1' => array('id' => array(), 'class' => array()),
        'h2' => array('id' => array(), 'class' => array()),
        'h3' => array('id' => array(), 'class' => array()),
        'h4' => array('id' => array(), 'class' => array()),
        'h5' => array('id' => array(), 'class' => array()),
        'h6' => array('id' => array(), 'class' => array()),
        'ul' => array('id' => array(), 'class' => array()),
        'ol' => array('id' => array(), 'class' => array()),
        'li' => array('id' => array(), 'class' => array()),
        'table' => array('id' => array(), 'class' => array()),
        'tr' => array('id' => array(), 'class' => array()),
        'td' => array('id' => array(), 'class' => array()),
        'th' => array('id' => array(), 'class' => array()),
        'meta' => array(
            'name' => array(),
            'content' => array(),
            'http-equiv' => array()
        ),
        'noscript' => array(),
        'style' => array(
            'type' => array()
        )
    );
}

function herepay_handle_payment_processing()
{
    // Verify nonce for security
    if (!isset($_POST['herepay_nonce'])) {
        wp_die(esc_html__('Security verification failed. Please try again.', 'herepay-for-woocommerce'));
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['herepay_nonce']));
    if (!wp_verify_nonce($nonce, 'herepay_process_payment')) {
        wp_die(esc_html__('Security verification failed. Please try again.', 'herepay-for-woocommerce'));
    }

    // Validate required fields first
    if (!isset($_POST['order_id']) || !isset($_POST['payment_code']) || !isset($_POST['amount'])) {
        wp_die(esc_html__('Required payment data missing.', 'herepay-for-woocommerce'));
    }

    // Extract and sanitize only the required fields for payment processing
    $order_id = intval($_POST['order_id']);
    $payment_code = sanitize_text_field(wp_unslash($_POST['payment_code']));
    $amount = sanitize_text_field(wp_unslash($_POST['amount']));
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $description = sanitize_text_field(wp_unslash($_POST['description'] ?? ''));
    $bank_prefix = sanitize_text_field(wp_unslash($_POST['bank_prefix'] ?? ''));
    $payment_method = sanitize_text_field(wp_unslash($_POST['payment_method'] ?? ''));
    $redirect_url = esc_url_raw(wp_unslash($_POST['redirect_url'] ?? ''));
    $created_at = sanitize_text_field(wp_unslash($_POST['created_at'] ?? ''));

    // Rebuild form data with sanitized values
    $form_data = [
        'payment_code' => $payment_code,
        'amount' => $amount,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'description' => $description,
        'bank_prefix' => $bank_prefix,
        'payment_method' => $payment_method,
        'redirect_url' => $redirect_url,
        'created_at' => $created_at
    ];

    $order = $order_id ? wc_get_order($order_id) : null;

    $gateway_options = get_option('woocommerce_herepay_payment_gateway_settings', array());
    $api_key = isset($gateway_options['api_key']) ? $gateway_options['api_key'] : '';
    $secret_key = isset($gateway_options['secret_key']) ? $gateway_options['secret_key'] : '';
    $private_key = isset($gateway_options['private_key']) ? $gateway_options['private_key'] : '';

    if (empty($api_key) || empty($secret_key) || empty($private_key)) {
        wp_die('Herepay credentials not configured. Please configure API Key, Secret Key, and Private Key in WooCommerce > Settings > Payments > Herepay.');
    }

    unset($form_data['checksum']);

    ksort($form_data);
    $concatenated_data = implode(',', $form_data);
    $checksum = hash_hmac('sha256', $concatenated_data, $private_key);
    $form_data['checksum'] = $checksum;

    $headers = [
        'SecretKey' => $secret_key,
        'XApiKey' => $api_key,
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];

    $response = wp_remote_post('https://uat.herepay.org/api/v1/herepay/initiate', [
        'method' => 'POST',
        'headers' => $headers,
        'body' => http_build_query($form_data),
        'timeout' => 30,
        'sslverify' => true
    ]);

    if (is_wp_error($response)) {
        wp_die('Error initiating payment: ' . esc_html($response->get_error_message()));
    } else {
        $body = wp_remote_retrieve_body($response);
        echo wp_kses($body, herepay_get_allowed_html());
    }
    exit;
}/**
 * Declare HPOS and Blocks compatibility
 */
function herepay_declare_compatibility()
{
    if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare HPOS compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);

        // Declare checkout blocks compatibility - REQUIRED for block checkout
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'herepay_declare_compatibility');

/**
 * Add Herepay Gateway to WooCommerce
 */
function add_herepay_payment_gateway($methods)
{
    $methods[] = 'Herepay_WC_Payment_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_herepay_payment_gateway');

/**
 * Register Herepay payment method for WooCommerce Blocks
 */
function herepay_register_payment_method_block()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry')) {
        return;
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Herepay_Blocks_Integration);
        }
    );
}
add_action('woocommerce_blocks_loaded', 'herepay_register_payment_method_block');

/**
 * Add custom links to plugin page
 */
function herepay_plugin_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=herepay_payment_gateway') . '">' . __('Settings', 'herepay-for-woocommerce') . '</a>';
    $docs_link = '<a href="https://herepay.readme.io" target="_blank">' . __('Documentation', 'herepay-for-woocommerce') . '</a>';

    array_unshift($links, $settings_link, $docs_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'herepay_plugin_action_links');

/**
 * Add settings link to plugins page
 */
function herepay_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) === $file) {
        $links[] = '<a href="' . admin_url('admin.php?page=herepay-settings') . '">' . __('Herepay Dashboard', 'herepay-for-woocommerce') . '</a>';
        $links[] = '<a href="https://herepay.org/support" target="_blank">' . __('Support', 'herepay-for-woocommerce') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'herepay_plugin_row_meta', 10, 2);

/**
 * Plugin activation hook
 */
function herepay_activate()
{
    // Add rewrite rule for redirect URL
    add_rewrite_rule('^herepay-redirect/?', 'index.php?herepay_redirect=1', 'top');

    // Flush rewrite rules
    flush_rewrite_rules();

    // Set flag to ensure rules are flushed
    update_option('herepay_rewrite_rules_flushed', true);
}

/**
 * Add Herepay rewrite rules
 */
function herepay_add_rewrite_rules()
{
    add_rewrite_rule('^herepay-redirect/?', 'index.php?herepay_redirect=1', 'top');
}

/**
 * Add Herepay query vars
 */
function herepay_add_query_vars($vars)
{
    $vars[] = 'herepay_redirect';
    return $vars;
}

/**
 * Handle Herepay redirect
 */
function herepay_handle_redirect()
{
    if (!get_query_var('herepay_redirect')) {
        return;
    }

    // Get the gateway instance
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!isset($gateways['herepay_payment_gateway'])) {
        wp_die('Herepay gateway not available');
    }

    $gateway = $gateways['herepay_payment_gateway'];
    $gateway->handle_herepay_redirect();
}
register_activation_hook(__FILE__, 'herepay_activate');

// Add hooks for rewrite rules and redirect handling
add_action('init', 'herepay_add_rewrite_rules');
add_filter('query_vars', 'herepay_add_query_vars');
add_action('template_redirect', 'herepay_handle_redirect');

/**
 * Plugin deactivation hook
 */
function herepay_deactivate()
{
    // Clean up rewrite rules
    flush_rewrite_rules();

    // Remove the option flag
    delete_option('herepay_rewrite_rules_flushed');
}
register_deactivation_hook(__FILE__, 'herepay_deactivate');

/**
 * Enqueue frontend scripts
 */
function herepay_enqueue_scripts()
{
    if (is_checkout()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'herepay-checkout',
            HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL . 'assets/checkout.js',
            ['jquery'],
            HEREPAY_FOR_WOOCOMMERCE_VERSION,
            true
        );

        wp_enqueue_style(
            'herepay-checkout-style',
            HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL . 'assets/checkout.css',
            [],
            HEREPAY_FOR_WOOCOMMERCE_VERSION
        );

        wp_localize_script('herepay-checkout', 'herepay_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'loading_text' => __('Processing payment...', 'herepay-for-woocommerce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'herepay_enqueue_scripts');
