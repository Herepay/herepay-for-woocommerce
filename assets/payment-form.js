document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('herepay-payment-form');
    
    if (!form) {
        return;
    }

    // Auto-submit the form after configured delay
    setTimeout(function() {
        form.submit();
    }, herepay_payment_form.auto_submit_delay);
});

// Global function for manual form submission
function herepay_submit_payment_form() {
    const form = document.getElementById('herepay-payment-form');
    if (form) {
        form.submit();
    }
}
