jQuery(document).ready(function($) {
    'use strict';
    
    // Add CSS for logo sizing
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .woocommerce-checkout-payment .payment_methods .payment_method_herepay_payment_gateway img {
                max-height: 24px;
                width: auto;
                vertical-align: middle;
                margin-left: 10px;
            }
            .woocommerce-checkout-payment .payment_methods .payment_method_herepay_payment_gateway label {
                display: flex;
                align-items: center;
            }
        `)
        .appendTo('head');
    
    var herepay_checkout = {
        
        init: function() {
            this.bindEvents();
            this.setupPaymentMethodSelection();
        },
        
        bindEvents: function() {
            // Handle payment method change
            $(document.body).on('change', 'input[name="payment_method"]', this.togglePaymentForm);
            
            // Handle bank selection change
            $(document.body).on('change', '#herepay_bank_prefix', this.updatePaymentMethod);
            
            // Validate form before submission
            $(document.body).on('checkout_place_order_herepay_payment_gateway', this.validateForm);
        },
        
        togglePaymentForm: function() {
            var selectedMethod = $('input[name="payment_method"]:checked').val();
            
            if (selectedMethod === 'herepay_payment_gateway') {
                $('#herepay-payment-form').slideDown();
            } else {
                $('#herepay-payment-form').slideUp();
            }
        },
        
        updatePaymentMethod: function() {
            var selectedOption = $(this).find('option:selected');
            var paymentMethod = selectedOption.data('method');
            $('#herepay_payment_method').val(paymentMethod || '');
            
            // Update display
            if (paymentMethod) {
                $('.herepay-selected-method').remove();
                $(this).after('<div class="herepay-selected-method" style="margin-top: 5px; font-size: 12px; color: #666;">Payment Method: ' + paymentMethod + '</div>');
            }
        },
        
        validateForm: function() {
            var bankPrefix = $('#herepay_bank_prefix').val();
            var paymentMethod = $('#herepay_payment_method').val();
            
            // Remove previous error messages
            $('.herepay-error').remove();
            
            if (!bankPrefix) {
                $('#herepay_bank_prefix').after('<div class="herepay-error" style="color: red; font-size: 12px; margin-top: 5px;">Please select a bank for payment.</div>');
                $('html, body').animate({
                    scrollTop: $('#herepay_bank_prefix').offset().top - 100
                }, 500);
                return false;
            }
            
            if (!paymentMethod) {
                $('#herepay_payment_method').after('<div class="herepay-error" style="color: red; font-size: 12px; margin-top: 5px;">Payment method not detected. Please select a bank again.</div>');
                return false;
            }
            
            // Show loading state
            $('.woocommerce-checkout-payment').block({
                message: herepay_params.loading_text,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            
            return true;
        },
        
        setupPaymentMethodSelection: function() {
            // Initialize form state
            this.togglePaymentForm();
            
            // Add some styling to the payment form
            $('#herepay-payment-form').css({
                'border': '1px solid #ddd',
                'padding': '15px',
                'border-radius': '4px',
                'background-color': '#f9f9f9',
                'margin-top': '10px'
            });
            
            // Style the select dropdown
            $('#herepay_bank_prefix').css({
                'padding': '8px',
                'border': '1px solid #ddd',
                'border-radius': '4px',
                'font-size': '14px'
            });
            
            // Add loading state to bank selection
            $('#herepay_bank_prefix').on('change', function() {
                var $this = $(this);
                if ($this.val()) {
                    $this.after('<span class="herepay-loading" style="margin-left: 10px; font-size: 12px; color: #666;">✓ Selected</span>');
                    setTimeout(function() {
                        $('.herepay-loading').fadeOut();
                    }, 1000);
                }
            });
        }
    };
    
    // Initialize when page loads
    herepay_checkout.init();
    
    // Re-initialize after AJAX updates
    $(document.body).on('updated_checkout', function() {
        herepay_checkout.init();
    });
    
    // Handle payment form errors
    $(document.body).on('checkout_error', function() {
        $('.woocommerce-checkout-payment').unblock();
        $('.herepay-error').show();
    });
    
});
