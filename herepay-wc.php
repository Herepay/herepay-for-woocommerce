<?php
/**
 * Plugin Name: Herepay Payment Gateway
 * Description: Herepay Payment Gateway for WooCommerce.
 * Version: 1.0
 * Author: Herepay
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load WooCommerce Payment Gateway
function herepay_payment_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) return;
    include_once 'init.php';
}
add_action('plugins_loaded', 'herepay_payment_gateway_init');

// Add Gateway to WooCommerce
function add_herepay_payment_gateway($methods) {
    $methods[] = 'WC_Herepay_Payment_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_herepay_payment_gateway');
