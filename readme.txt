=== Herepay Payment Gateway ===
Contributors: herepay, adisazizan, aleprosli
Tags: payment, gateway, woocommerce, herepay, malaysia, online banking, ecommerce
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Secure payment gateway for WooCommerce that integrates Herepay payment services, enabling Malaysian businesses to accept online payments seamlessly.

== Description ==

Herepay Payment Gateway for WooCommerce is a comprehensive payment solution that integrates Herepay's secure payment services with your WooCommerce store. This plugin enables Malaysian businesses to accept online payments through various payment methods including online banking and corporate banking.

**Key Features:**

* **Complete Herepay API Integration** - Seamlessly integrate with Herepay's payment infrastructure
* **Multiple Payment Methods** - Support for Online Banking, Corporate Banking, and more
* **Real-time Payment Tracking** - Instant payment status updates and notifications
* **Secure Checksum Validation** - Advanced security with HMAC-SHA256 checksum verification
* **Dual Environment Support** - Both Sandbox and Production environments available
* **Admin Dashboard** - Comprehensive transaction management and monitoring
* **Automatic Order Updates** - Orders are automatically updated based on payment status
* **Webhook Support** - Real-time payment notifications from Herepay
* **Mobile Responsive** - Optimized payment forms for all devices
* **HPOS Compatible** - Full compatibility with WooCommerce High-Performance Order Storage

**Why Choose Herepay?**

* Trusted by thousands of Malaysian businesses
* Bank-level security standards
* Competitive transaction fees
* 24/7 customer support
* Fast settlement times
* Easy integration process

**Supported Payment Methods:**

* Online Banking (Major Malaysian banks)
* Corporate Banking
* E-wallet integrations
* Credit/Debit card processing

**Security Features:**

* PCI DSS compliant
* SSL encryption for all transactions
* Advanced fraud detection
* Secure webhook validation
* No sensitive data stored locally

== Installation ==

**Automatic Installation (Recommended):**

1. Login to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Search for "Herepay Payment Gateway"
4. Click **Install Now** and then **Activate**

**Manual Installation:**

1. Download the plugin zip file
2. Login to your WordPress admin dashboard
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Choose the downloaded zip file and click **Install Now**
5. After installation, click **Activate Plugin**

**FTP Installation:**

1. Download and extract the plugin files
2. Upload the `herepay-wc` folder to `/wp-content/plugins/` directory via FTP
3. Login to WordPress admin and activate the plugin under **Plugins > Installed Plugins**

== Configuration ==

**Step 1: Get Herepay Credentials**

1. Sign up for a Herepay merchant account at [https://herepay.org](https://herepay.org)
2. Complete the verification process
3. Access your merchant dashboard
4. Navigate to **API Settings** and obtain:
   * API Key
   * Secret Key
   * Private Key

**Step 2: Configure the Plugin**

1. In WordPress admin, go to **WooCommerce > Settings > Payments**
2. Find **Herepay Payment Gateway** and click **Manage**
3. Configure the following:
   * **Enable/Disable**: Check to enable the payment method
   * **Title**: Payment method title (shown to customers)
   * **Description**: Payment description at checkout
   * **Environment**: Select Sandbox for testing, Production for live payments
   * **API Key**: Enter your Herepay API Key
   * **Secret Key**: Enter your Herepay Secret Key
   * **Private Key**: Enter your Herepay Private Key
4. Click **Save Changes**

**Step 3: Configure Webhook (Important)**

1. Login to your Herepay merchant dashboard
2. Navigate to **Webhook Settings**
3. Set the webhook URL to: `https://yourdomain.com/wc-api/wc_herepay_payment_gateway`
4. Enable webhook notifications

**Step 4: Test the Integration**

1. Set environment to "Sandbox"
2. Use test credentials provided by Herepay
3. Make a test purchase to verify the payment flow
4. Check order status updates in WooCommerce admin

== Frequently Asked Questions ==

= Do I need a Herepay merchant account? =

Yes, you need a valid Herepay merchant account to use this plugin. You can sign up at [https://herepay.org](https://herepay.org).

= Which payment methods are supported? =

The plugin supports all payment methods available in your Herepay merchant account, including online banking from major Malaysian banks, corporate banking, and e-wallet services.

= Is this plugin secure? =

Yes, the plugin follows WordPress and WooCommerce security best practices. All transactions are processed through Herepay's PCI DSS compliant infrastructure with SSL encryption.

= Can I test the plugin before going live? =

Absolutely! The plugin includes sandbox mode for testing. Use the sandbox environment with test credentials provided by Herepay to thoroughly test the payment flow.

= What happens if a payment fails? =

If a payment fails, the order status is automatically updated to "Failed" and the customer is notified. Stock levels are restored if they were previously reduced.

= Does the plugin work with WordPress multisite? =

Yes, the plugin is compatible with WordPress multisite installations. Each site can have its own Herepay configuration.

= Is the plugin compatible with the latest WooCommerce version? =

Yes, the plugin is regularly updated to maintain compatibility with the latest versions of WordPress and WooCommerce. It's currently tested up to WooCommerce 9.6.0.

= How do I handle refunds? =

Refunds should be processed through your Herepay merchant dashboard. The plugin will receive webhook notifications about refund status updates.

= Can I customize the payment form appearance? =

Yes, you can customize the payment form using CSS. The plugin includes CSS classes that you can override in your theme's stylesheet.

= What if I encounter issues during integration? =

First, check the troubleshooting section in the plugin documentation. If issues persist, contact Herepay support or check the WordPress support forums.

== Screenshots ==

1. **Payment Gateway Settings** - Configure your Herepay credentials and settings
2. **Checkout Payment Options** - Customers can select Herepay as their payment method
3. **Bank Selection** - Dynamic bank selection based on available payment channels
4. **Transaction Dashboard** - Admin interface for managing transactions
5. **Order Management** - Automatic order status updates based on payment status
6. **Mobile Responsive** - Optimized for mobile and tablet devices

== Changelog ==

= 1.0.0 =
* Initial release
* Complete Herepay API integration
* Support for multiple payment methods
* Real-time payment status tracking
* Secure checksum validation
* Sandbox and Production environment support
* Admin dashboard for transaction management
* Automatic order status updates
* Webhook support for payment notifications
* Mobile-responsive payment forms
* HPOS (High-Performance Order Storage) compatibility
* WordPress 6.7.1 compatibility
* WooCommerce 9.6.0 compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of Herepay Payment Gateway for WooCommerce. No upgrade required.

== Requirements ==

* WordPress 5.0 or higher
* WooCommerce 5.0 or higher
* PHP 8.1 or higher
* cURL extension enabled
* SSL certificate (required for production)
* Valid Herepay merchant account

== Support ==

For support and documentation:

* **Plugin Documentation**: Available in the plugin folder
* **Herepay API Documentation**: [https://herepay.readme.io](https://herepay.readme.io)
* **Herepay Support**: [https://herepay.org/support](https://herepay.org/support)
* **WordPress Support Forums**: [WordPress.org Plugin Support](https://wordpress.org/support/plugin/herepay-payment-gateway/)

== Privacy Policy ==

This plugin integrates with Herepay's payment services. When customers make payments:

* Customer payment data is processed by Herepay according to their privacy policy
* No sensitive payment information is stored on your WordPress site
* Transaction references and order details are stored for order management
* Webhook callbacks are logged for audit and debugging purposes

Please review Herepay's privacy policy at [https://herepay.org/privacy](https://herepay.org/privacy) for complete information about data handling.

== Additional Information ==

**Developer**: Herepay  
**Plugin URI**: [https://github.com/Herepay/herepay-wc](https://github.com/Herepay/herepay-wc)  
**Author URI**: [https://herepay.org](https://herepay.org)

This plugin is developed and maintained by the Herepay team to provide seamless payment integration for Malaysian businesses and organization using WooCommerce.
