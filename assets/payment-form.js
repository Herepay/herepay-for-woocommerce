document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('herepay-payment-form');
    
    if (!form) {
        return;
    }
    
    // Debug logging if enabled
    if (herepay_payment_form.debug_mode) {
        console.log('Form found:', form);
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        console.log('Form elements count:', form.elements.length);
        
        // Log all form data
        for (let i = 0; i < form.elements.length; i++) {
            const element = form.elements[i];
            console.log('Form field:', element.name, '=', element.value);
        }
    }
    
    // Auto-submit the form after configured delay
    setTimeout(function() {
        if (herepay_payment_form.debug_mode) {
            console.log('Auto-submitting form...');
        }
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
