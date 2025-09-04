/**
 * Herepay Payment Method Block Integration
 * 
 * This handles the Herepay payment method in WooCommerce Block-based checkout
 */

const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = wp.element;
const { __ } = wp.i18n;
const { decodeEntities } = wp.htmlEntities;

/**
 * Content component for Herepay payment method
 */
const Content = () => {
    const [selectedBank, setSelectedBank] = useState('');
    const [selectedMethod, setSelectedMethod] = useState('');
    const [channels, setChannels] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    // Get payment channels data from localized script
    useEffect(() => {
        if (window.herepayBlocksParams && window.herepayBlocksParams.paymentChannels) {
            setChannels(window.herepayBlocksParams.paymentChannels);
            setLoading(false);
        } else {
            setError(__('Unable to load payment channels', 'woocommerce'));
            setLoading(false);
        }
    }, []);

    // Handle bank selection
    const handleBankChange = (event) => {
        const bankPrefix = event.target.value;
        setSelectedBank(bankPrefix);
        
        // Find the selected channel to get the payment method
        const selectedChannel = channels.find(channel => channel.prefix === bankPrefix);
        if (selectedChannel) {
            setSelectedMethod(selectedChannel.method);
        } else {
            setSelectedMethod('');
        }
    };

    if (loading) {
        return createElement(
            'div',
            { style: { padding: '15px', textAlign: 'center' } },
            __('Loading payment options...', 'woocommerce')
        );
    }

    if (error) {
        return createElement(
            'div',
            { style: { padding: '15px', backgroundColor: '#f8d7da', border: '1px solid #f5c6cb', borderRadius: '4px', color: '#721c24' } },
            createElement('strong', null, __('Payment Error:', 'woocommerce')),
            createElement('br'),
            error
        );
    }

    if (!channels || channels.length === 0) {
        return createElement(
            'div',
            { style: { padding: '15px', backgroundColor: '#fff3cd', border: '1px solid #ffeaa7', borderRadius: '4px', color: '#856404' } },
            createElement('strong', null, __('Configuration Required:', 'woocommerce')),
            createElement('br'),
            __('Please configure your Herepay API credentials.', 'woocommerce')
        );
    }

    return createElement(
        'div',
        { className: 'herepay-payment-form', style: { padding: '15px', border: '1px solid #ddd', borderRadius: '4px', backgroundColor: '#f9f9f9', marginTop: '10px' } },
        [
            // Description
            window.herepayBlocksParams.description && createElement(
                'p',
                { key: 'description', style: { marginBottom: '15px' } },
                decodeEntities(window.herepayBlocksParams.description)
            ),
            
            // Bank selection label
            createElement(
                'label',
                { 
                    key: 'label',
                    htmlFor: 'herepay-bank-select',
                    style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' }
                },
                __('Select Bank', 'woocommerce'),
                ' ',
                createElement('span', { style: { color: 'red' } }, '*')
            ),
            
            // Bank selection dropdown
            createElement(
                'select',
                {
                    key: 'select',
                    id: 'herepay-bank-select',
                    value: selectedBank,
                    onChange: handleBankChange,
                    required: true,
                    style: { 
                        width: '100%', 
                        marginBottom: '10px', 
                        padding: '8px', 
                        border: '1px solid #ddd', 
                        borderRadius: '4px', 
                        fontSize: '14px' 
                    }
                },
                [
                    createElement('option', { key: 'default', value: '' }, __('Select a bank...', 'woocommerce')),
                    ...channels.map(channel => 
                        createElement(
                            'option',
                            {
                                key: channel.prefix,
                                value: channel.prefix,
                                'data-method': channel.method
                            },
                            `${channel.name} (${channel.method})`
                        )
                    )
                ]
            ),
            
            // Selected method feedback
            selectedMethod && createElement(
                'div',
                {
                    key: 'feedback',
                    style: { 
                        marginTop: '5px', 
                        fontSize: '12px', 
                        color: '#666',
                        padding: '5px',
                        backgroundColor: '#e8f5e8',
                        borderRadius: '3px'
                    }
                },
                '✓ ',
                __('Payment Method:', 'woocommerce'),
                ' ',
                selectedMethod
            ),
            
            // Hidden inputs for form data
            createElement('input', {
                key: 'bank-input',
                type: 'hidden',
                name: 'herepay_bank_prefix',
                value: selectedBank
            }),
            createElement('input', {
                key: 'method-input',
                type: 'hidden',
                name: 'herepay_payment_method',
                value: selectedMethod
            })
        ].filter(Boolean)
    );
};

/**
 * Label component for Herepay payment method
 */
const Label = (props) => {
    const { PaymentMethodLabel } = props.components;
    const logoUrl = window.herepayBlocksParams?.logoUrl;
    
    return createElement(PaymentMethodLabel, {
        text: decodeEntities(window.herepayBlocksParams?.title || __('Herepay Payment Gateway', 'woocommerce')),
        icon: logoUrl ? createElement('img', {
            src: logoUrl,
            alt: 'Herepay',
            style: { height: '24px', width: 'auto' }
        }) : null
    });
};

/**
 * Herepay payment method configuration
 */
const herepayPaymentMethod = {
    name: 'herepay_payment_gateway',
    label: Label,
    content: Content,
    edit: Content,
    canMakePayment: () => true,
    ariaLabel: decodeEntities(window.herepayBlocksParams?.title || __('Herepay Payment Gateway', 'woocommerce')),
    supports: {
        features: window.herepayBlocksParams?.supports || ['products']
    }
};

// Register the payment method
registerPaymentMethod(herepayPaymentMethod);
