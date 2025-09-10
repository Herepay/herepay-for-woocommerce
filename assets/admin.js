jQuery(document).ready(function($) {
    // Test API Connection
    $('#test-connection').on('click', function() {
        var button = $(this);
        var resultDiv = $('#connection-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('<div class="spinner is-active"></div>');
        
        $.ajax({
            url: herepay_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'herepay_test_connection',
                nonce: herepay_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var message = response.data.message;
                    if (response.data.note) {
                        message += ' (' + response.data.note + ')';
                    }
                    resultDiv.html('<div class="notice notice-success"><p>' + message + ' (' + response.data.channels_count + ' payment channels available)</p></div>');
                } else {
                    var errorMsg = response.data.message;
                    if (response.data.debug) {
                        errorMsg += '<br><small>Debug: ' + response.data.debug + '</small>';
                    }
                    resultDiv.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Connection test failed. Please try again.';
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } catch (e) {
                        // Fallback to generic error
                    }
                }
                resultDiv.html('<div class="notice notice-error"><p>' + errorMsg + '</p><small>Error details logged to console.</small></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Test API Connection');
            }
        });
    });
    
    // Check Transaction Status
    $('#check-transaction').on('click', function() {
        checkTransactionStatus($('#transaction-code').val(), $('#transaction-result'));
    });
    
    // Check status buttons in table
    $('.check-status-btn').on('click', function() {
        var paymentCode = $(this).data('code');
        var resultDiv = $('<div class="transaction-status-result"></div>');
        $(this).closest('tr').after('<tr class="transaction-status-row"><td colspan="6"></td></tr>');
        $(this).closest('tr').next().find('td').append(resultDiv);
        
        checkTransactionStatus(paymentCode, resultDiv);
    });
    
    function checkTransactionStatus(paymentCode, resultDiv) {
        if (!paymentCode.trim()) {
            resultDiv.html('<div class="notice notice-warning"><p>Please enter a payment code.</p></div>');
            return;
        }
        
        resultDiv.html('<div class="spinner is-active"></div>');
        
        $.ajax({
            url: herepay_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'herepay_check_transaction',
                payment_code: paymentCode,
                nonce: herepay_ajax.nonce
            },
            success: function(response) {
                
                if (response.success) {
                    var data = response.data.data || response.data || {};
                    
                    var statusHtml = '<div class="notice notice-info">';
                    statusHtml += '<h4>Transaction Status for: ' + paymentCode + '</h4>';
                    
                    // Check if we have any data to display
                    if (Object.keys(data).length === 0) {
                        statusHtml += '<p>No transaction data found for this payment code.</p>';
                    } else {
                        statusHtml += '<table class="transaction-details-table">';
                        
                        // Handle status field with type checking
                        if (data.status) {
                            var statusValue = String(data.status); // Convert to string to be safe
                            var statusClass = statusValue.toLowerCase().replace(/[^a-z0-9]/g, '-');
                            statusHtml += '<tr><th>Status:</th><td><span class="status-badge status-' + statusClass + '">' + statusValue + '</span></td></tr>';
                        }
                        
                        // Handle other fields
                        if (data.amount) {
                            statusHtml += '<tr><th>Amount:</th><td>RM ' + data.amount + '</td></tr>';
                        }
                        if (data.transaction_id) {
                            statusHtml += '<tr><th>Transaction ID:</th><td>' + data.transaction_id + '</td></tr>';
                        }
                        if (data.payment_method) {
                            statusHtml += '<tr><th>Payment Method:</th><td>' + data.payment_method + '</td></tr>';
                        }
                        if (data.bank_prefix) {
                            statusHtml += '<tr><th>Bank:</th><td>' + data.bank_prefix + '</td></tr>';
                        }
                        if (data.created_at) {
                            statusHtml += '<tr><th>Created:</th><td>' + data.created_at + '</td></tr>';
                        }
                        if (data.updated_at) {
                            statusHtml += '<tr><th>Updated:</th><td>' + data.updated_at + '</td></tr>';
                        }
                        
                        // Show any additional fields
                        for (var key in data) {
                            if (data.hasOwnProperty(key) && !['status', 'amount', 'transaction_id', 'payment_method', 'bank_prefix', 'created_at', 'updated_at'].includes(key)) {
                                statusHtml += '<tr><th>' + key.charAt(0).toUpperCase() + key.slice(1) + ':</th><td>' + data[key] + '</td></tr>';
                            }
                        }
                        
                        statusHtml += '</table>';
                    }
                    
                    statusHtml += '</div>';
                    resultDiv.html(statusHtml);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    resultDiv.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function() {
                resultDiv.html('<div class="notice notice-error"><p>Failed to check transaction status. Please try again.</p></div>');
            }
        });
    }
    
    // Copy webhook URL
    $('.copy-webhook-url').on('click', function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            alert('Webhook URL copied to clipboard!');
        });
    });
    
    // Auto-refresh transaction table
    if ($('.herepay-logs-card').length) {
        setInterval(function() {
            // Auto refresh every 30 seconds
            location.reload();
        }, 30000);
    }
});
