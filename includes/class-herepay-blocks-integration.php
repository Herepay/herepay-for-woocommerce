<?php
/**
 * Herepay WooCommerce Blocks Integration
 * 
 * Handles integration with WooCommerce Block-based checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Herepay payment method integration for WooCommerce Blocks
 */
final class Herepay_Blocks_Integration extends AbstractPaymentMethodType {

    /**
     * The gateway instance.
     *
     * @var Herepay_WC_Payment_Gateway
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'herepay_payment_gateway';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_herepay_payment_gateway_settings', []);
        $this->gateway = new Herepay_WC_Payment_Gateway();
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path       = '/assets/blocks/herepay-blocks.js';
        $script_asset_path = HEREPAY_FOR_WOOCOMMERCE_PLUGIN_PATH . 'assets/blocks/herepay-blocks.asset.php';
        $script_asset      = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => HEREPAY_FOR_WOOCOMMERCE_VERSION
            );
        $script_url        = HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL . $script_path;

        wp_register_script(
            'herepay-blocks-integration',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_register_style(
            'herepay-bank-picker',
            HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL . 'assets/herepay-bank-picker.css',
            [],
            HEREPAY_FOR_WOOCOMMERCE_VERSION
        );
        wp_register_style(
            'herepay-blocks-style',
            HEREPAY_FOR_WOOCOMMERCE_PLUGIN_URL . 'assets/blocks/herepay-blocks.css',
            ['herepay-bank-picker'],
            HEREPAY_FOR_WOOCOMMERCE_VERSION
        );
        wp_enqueue_style('herepay-blocks-style');

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('herepay-blocks-integration', 'herepay-for-woocommerce');
        }

        // Never pass API/secret keys to the browser: only what the picker needs.
        $groups = $this->gateway->get_bank_groups();
        wp_localize_script(
            'herepay-blocks-integration',
            'herepayBlocksParams',
            [
                'title'       => $this->get_herepay_setting('title'),
                'description' => $this->get_herepay_setting('description'),
                'supports'    => array_values(array_filter($this->gateway->supports, [$this->gateway, 'supports'])),
                'logoUrl'     => $this->gateway->icon,
                'configured'  => !empty($this->get_herepay_setting('api_key')) && !empty($this->get_herepay_setting('secret_key')),
                'bankGroups'  => is_array($groups) ? $groups : [],
            ]
        );

        return ['herepay-blocks-integration'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->get_herepay_setting('title'),
            'description' => $this->get_herepay_setting('description'),
            'supports'    => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'logo_url'    => $this->gateway->icon,
        ];
    }

    /**
     * Get setting value - renamed to avoid parent class conflict
     *
     * @param string $key
     * @return mixed
     */
    protected function get_herepay_setting($key) {
        return isset($this->settings[$key]) ? $this->settings[$key] : '';
    }
}
