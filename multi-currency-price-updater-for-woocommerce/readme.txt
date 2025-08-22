=== Multi-Currency Price Updater for WooCommerce ===
Contributors: eraser25
Tags: woocommerce, currency, price, updater, exchange
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically update WooCommerce product prices using real-time exchange rates for USD, EUR, GBP, JPY, CAD, and TRY currencies.

== Description ==

Multi-Currency Price Updater for WooCommerce automatically synchronizes your product prices with real-time exchange rates, ensuring your customers always see accurate pricing.

**Supported Currencies:**
* USD - US Dollar
* EUR - Euro  
* GBP - British Pound
* JPY - Japanese Yen
* CAD - Canadian Dollar
* TRY - Turkish Lira

**Key Features:**
* Real-time exchange rate updates
* Individual currency selection per product
* Bulk product update operations
* Comprehensive price change history
* Automated backup system
* Email alert notifications
* Category-based filtering
* Modern responsive admin interface

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/multi-currency-price-updater-for-woocommerce/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Make sure WooCommerce is installed and activated
4. Use the Currency Updater screen to configure your settings

== Frequently Asked Questions ==

= Is WooCommerce required? =
Yes, this plugin works with WooCommerce and requires it to be installed and active.

= Which API is used for exchange rates? =
We use finans.truncgil.com API for current Turkish exchange rates.

= How often are prices updated? =
By default every 60 seconds, but you can change this in the settings.

== Changelog ==

= 2.1.1 =
* Fixed WordPress.org compliance issues
* Added proper function prefixes
* Improved security with nonce verification
* Updated descriptions to English

= 2.1.0 =
* Added multi-currency support
* Added variation support
* Enhanced admin interface
* Added backup system

== Upgrade Notice ==

= 2.1.1 =
This update fixes WordPress.org compliance issues and improves security.