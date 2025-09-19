# Herepay Payment Gateway for WooCommerce

A secure and reliable payment gateway plugin that integrates Herepay payment services with WooCommerce, enabling Malaysian businesses to accept online payments seamlessly.

## Features

- ✅ Complete Herepay API integration
- ✅ Support for multiple payment methods (Online Banking, Corporate Banking, etc.)
- ✅ Real-time payment status tracking
- ✅ Secure checksum validation
- ✅ Sandbox and Production environment support
- ✅ Admin dashboard for transaction management
- ✅ Automatic order status updates
- ✅ Webhook support for payment notifications
- ✅ Mobile-responsive payment forms

## Compatibility

- **PHP**: 8.1 or higher
- **WordPress**: 5.0 or higher (tested up to 6.7.1)
- **WooCommerce**: 5.0 or higher (tested up to 9.6.0)

## Installation

### Method 1: WordPress Admin Panel

1. Download the plugin ZIP file
2. Login to your WordPress Admin Panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the downloaded ZIP file and click **Install Now**
6. After installation, click **Activate Plugin**

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `herepay-for-woocommerce` folder to `/wp-content/plugins/` directory
3. Login to WordPress Admin Panel
4. Navigate to **Plugins > Installed Plugins**
5. Find "Herepay Payment Gateway" and click **Activate**

### Method 3: FTP Upload

1. Extract the plugin files
2. Connect to your server via FTP
3. Upload the `herepay-for-woocommerce` folder to `/wp-content/plugins/`
4. Login to WordPress Admin and activate the plugin

## Configuration

### Step 1: Get Herepay API Credentials

1. Sign up for a Herepay merchant account at [https://herepay.org](https://herepay.org)
2. Access your merchant dashboard
3. Navigate to **API Settings** or **Developer Tools**
4. Copy your:
   - API Key
   - Secret Key
   - Private Key (for checksum generation)

### Step 2: Configure the Plugin

1. In WordPress Admin, go to **WooCommerce > Settings**
2. Click the **Payments** tab
3. Find **Herepay Payment Gateway** and click **Manage**
4. Configure the following settings:

#### Basic Settings
- **Enable/Disable**: Check to enable the payment method
- **Title**: Display name for customers (e.g., "Herepay Online Banking")
- **Description**: Payment method description shown at checkout

#### API Configuration
- **Environment**: Select "Sandbox" for testing or "Production" for live payments
- **API Key**: Enter your Herepay API Key
- **Secret Key**: Enter your Herepay Secret Key
- **Private Key**: Enter your Herepay Private Key

#### URLs (Auto-configured)
- **Redirect URL**: Leave empty to use default success page
- **Callback URL**: Webhook URL (automatically set)

5. Click **Save Changes**

### Step 3: Configure Webhook in Herepay Dashboard

1. Login to your Herepay merchant dashboard
2. Navigate to **Webhook Settings** or **API Configuration**
3. Set the webhook URL to:
   ```
   https://yourdomain.com/wc-api/wc_herepay_payment_gateway
   ```
4. Enable webhook notifications for payment status updates

### Step 4: Test the Integration

1. Set environment to "Sandbox"
2. Use Herepay test credentials
3. Make a test purchase on your website
4. Verify payment flow and order status updates

## Usage

### For Customers

1. Add products to cart and proceed to checkout
2. Select "Herepay Payment Gateway" as payment method
3. Choose your preferred bank from the dropdown
4. Complete the payment on Herepay's secure payment page
5. Return to your store to view order confirmation

### For Store Administrators

#### Viewing Transactions

1. Go to **WooCommerce > Herepay** in WordPress Admin
2. View transaction status, payment codes, and order details
3. Use the search function to find specific transactions

#### Checking Payment Status

1. Navigate to **WooCommerce > Herepay**
2. Enter a payment code in the "Check Transaction Status" field
3. Click **Check Status** to retrieve real-time information

#### Testing API Connection

1. Go to **WooCommerce > Herepay**
2. Click **Test API Connection**
3. Verify that your credentials are working correctly

## API Endpoints

The plugin integrates with the following Herepay API endpoints:

- **Payment Channels**: `GET /api/v1/herepay/payment/channels`
- **Initiate Payment**: `POST /api/v1/herepay/initiate`
- **Transaction Status**: `GET /api/v1/herepay/transactions/{reference_code}`

## Webhook Handling

The plugin automatically handles webhook notifications from Herepay:

- **URL**: `/wc-api/wc_herepay_payment_gateway`
- **Method**: POST
- **Content-Type**: application/json

Supported payment statuses:
- `success` / `completed` → Order marked as paid
- `failed` / `cancelled` → Order marked as failed
- `pending` → Order kept in pending status

## Troubleshooting

### Common Issues

#### "Unable to load payment channels"
- Verify API credentials are correct
- Check internet connectivity
- Ensure Herepay service is operational

#### "Checksum verification failed"
- Verify Private Key is correct
- Check that all required fields are being sent
- Ensure data formatting matches API requirements

#### Orders not updating automatically
- Verify webhook URL is configured in Herepay dashboard
- Check that webhook URL is accessible from external servers
- Review order notes for webhook callbacks

### Debug Mode

Enable WordPress debug mode to view detailed error logs:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs will be saved to `/wp-content/debug.log`

### Testing Checklist

- [ ] API credentials configured correctly
- [ ] Test mode enabled for initial testing
- [ ] Payment channels loading successfully
- [ ] Test transactions completing successfully
- [ ] Webhook URL accessible
- [ ] Order status updates working
- [ ] Email notifications sent correctly

## Security

### Best Practices

- Always use HTTPS in production
- Keep API credentials secure and private
- Regularly update the plugin
- Monitor transaction logs for suspicious activity
- Use strong WordPress admin passwords

### Data Protection

- Customer payment data is processed by Herepay
- No sensitive payment information is stored locally
- All API communications use secure HTTPS
- Webhook callbacks are logged for audit purposes

## Support

### Documentation
- [Herepay API Documentation](https://herepay.readme.io)
- [WooCommerce Documentation](https://docs.woocommerce.com)

### Getting Help

1. Check this README for common solutions
2. Review WordPress and WooCommerce error logs
3. Test in sandbox mode to isolate issues
4. Contact Herepay support for API-related issues
5. Contact plugin developer for WordPress-specific issues

### Contact Information

- **Herepay Support**: [https://herepay.org/support](https://herepay.org/support)
- **Plugin Repository**: [GitHub Repository URL]
- **Email**: support@herepay.org

## Changelog

### Version 1.0.1
- Enhance input validation and sanization in processing 

### Version 1.0.0
- Initial release
- Complete Herepay API integration
- Admin dashboard for transaction management
- Webhook support for payment notifications
- Multi-language support ready
- Mobile-responsive design

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

We welcome contributions! Please see our contributing guidelines for more information.

---

**Note**: This plugin requires a valid Herepay merchant account. Please visit [https://herepay.org](https://herepay.org) to sign up.
