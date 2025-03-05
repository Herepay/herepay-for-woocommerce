<?php

class WC_Herepay_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'herepay_payment_gateway';
        $this->method_title = __('Herepay Payment Gateway', 'woocommerce');
        $this->method_description = __('Herepay Payment Gateway integration.', 'woocommerce');
        $this->supports = ['products'];

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Assign settings values
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Herepay Payment Gateway', 'woocommerce'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('Payment method title shown at checkout.', 'woocommerce'),
                'default' => __('Herepay Payment Gateway', 'woocommerce')
            ],
            'description' => [
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Description shown to customers at checkout.', 'woocommerce'),
                'default' => __('Pay securely using Herepay Payment Gateway.', 'woocommerce')
            ]
        ];
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Simulate payment success
        $order->payment_complete();
        $order->reduce_order_stock();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }
}
?>
