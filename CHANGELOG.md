# Changelog

All notable changes to the Herepay Payment Gateway for WooCommerce will be documented in this file.

## [1.0.4] - 2026-11-16

### Added
- Bank picker at checkout: Online Banking and Corporate Banking in an accordion with searchable bank tiles and bank logos (`img_url` from the Herepay API).
- Choosing a bank closes the list and shows the chosen bank in the header with a "Change" link.
- The same picker in classic and block checkout; mobile friendly; namespaced styles so Bootstrap 3-5 and Tailwind v2-v4 themes do not change it.
- Error page with a "Back to payment" button and an order note when Herepay rejects a payment.
- Settings notes: Redirect and Callback URLs need a live HTTPS server, with a warning on local or non-HTTPS stores.

### Changed
- Only the Herepay logo is shown next to the payment method.
- Herepay logos cropped and used in checkout, redirect page, gateway settings and admin page.
- Redesigned redirect page, now showing the bank name.
- Documentation links now point to https://docs.herepay.org.

### Fixed
- **Security**: block checkout no longer exposes the API key and secret key in the page.
- Block checkout now sends the chosen bank to the server.
- Redirect page CSS/JS no longer loads on the checkout page (it changed the page layout and caused a JavaScript error).

## [1.0.3] - 2026-04-22

### Added
- Update URL Production.

## [1.0.2] - 2026-01-31

### Added
- Added new assets.
- Added image to README for Herepay Payment Gateway.

## [1.0.1] - 2025-09-19

### Enhanced
- **Security**: Enhanced input validation and sanitization in payment processing
- **Data Security**: Improved sanitization of all form inputs including payment data, customer information, and transaction details
- **Input Validation**: Strengthened validation for required payment fields before processing
- **Code Quality**: Enhanced security practices in payment handling workflow

### Fixed
- Improved data sanitization for payment form submissions
- Enhanced validation of payment processing parameters
- Strengthened security checks for payment data handling

## [1.0.0] - 2025-08-30

### Added
- Initial release of Herepay Payment Gateway for WooCommerce
- Complete integration with Herepay API
- Support for multiple payment channels (Online Banking, Corporate Banking, etc.)
- Real-time payment status checking
- Secure checksum generation and validation
- Admin dashboard for transaction management
- Webhook support for automatic order updates
- Payment form with bank selection
- Sandbox and Production environment support
- API connection testing functionality
- Transaction status lookup tool
- Mobile-responsive payment forms
- WordPress and WooCommerce compatibility checks
- Plugin activation/deactivation hooks
- Multi-language support structure
- Comprehensive error handling and logging
- Security features including HTTPS enforcement
- Admin CSS and JavaScript for enhanced user experience
- Frontend JavaScript for smooth checkout process
- Detailed documentation and setup instructions

### Features
- **Payment Processing**: Seamless integration with Herepay payment API
- **Bank Selection**: Dynamic loading of available payment channels
- **Security**: SHA256 checksum validation for all transactions
- **Admin Tools**: Dashboard for monitoring and managing payments
- **Webhooks**: Automatic order status updates via callback handling
- **Testing**: Sandbox mode for safe development and testing
- **Responsive Design**: Mobile-friendly payment forms and admin interface
- **Error Handling**: Comprehensive error messages and debugging tools
- **Documentation**: Complete setup and troubleshooting guide

### Technical Details
- **PHP Version**: 8.1+ compatibility
- **WordPress**: 5.0+ (tested up to 6.7.1)
- **WooCommerce**: 5.0+ (tested up to 9.6.0)
- **API Integration**: Herepay v1 API
- **Security Standards**: PCI DSS compliant payment processing
- **Performance**: Optimized for speed and reliability

### Files Added
```
herepay-for-woocommerce/
├── herepay-for-woocommerce.php (Main plugin file)
├── init.php (Payment gateway class)
├── README.md (Documentation)
├── CHANGELOG.md (This file)
├── includes/
│   ├── class-herepay-payment-form.php (Payment form handler)
│   └── class-herepay-admin.php (Admin dashboard)
└── assets/
    ├── admin.css (Admin styling)
    ├── admin.js (Admin functionality)
    └── checkout.js (Frontend checkout handling)
```

### API Endpoints Integrated
- `GET /api/v1/herepay/payment/channels` - Retrieve available payment methods
- `POST /api/v1/herepay/initiate` - Initiate payment transaction
- `GET /api/v1/herepay/transactions/{reference_code}` - Check transaction status

### Configuration Options
- Environment selection (Sandbox/Production)
- API credentials management (API Key, Secret Key, Private Key)
- Payment method customization (Title, Description)
- Webhook URL configuration
- Redirect URL settings

### Admin Features
- Gateway status monitoring
- API connection testing
- Transaction status checking
- Recent transactions display
- Webhook URL management
- Documentation links
- Support contact information

### Security Features
- Secure API credential storage
- HTTPS enforcement for payment processing
- Checksum validation for all transactions
- Webhook signature verification
- Input sanitization and validation
- Error message filtering

### Future Enhancements (Planned)
- Multi-currency support
- Refund processing capability
- Advanced reporting and analytics
- Subscription payment support
- Enhanced fraud detection
- Additional payment methods
- Custom styling options
- Advanced webhook configurations

---

For detailed information about each feature, please refer to the README.md file.
For support and updates, visit: https://herepay.org
