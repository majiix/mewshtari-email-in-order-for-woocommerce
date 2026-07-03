=== Mewshtari Email in Order for WooCommerce ===
Contributors: micromax2
Tags: woocommerce, email, order, crm, customer
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a customizable WYSIWYG email panel to the WooCommerce order details page to send personalized emails directly to customers and automate order status changes.

== Description ==

Mewshtari Email in Order for WooCommerce allows store administrators to communicate custom notes, messages, and updates directly to their customers from the WooCommerce order edit page.

=== Features ===
* **WYSIWYG Editor**: Directly compose and format messages before sending.
* **5 Selectable Templates**: Store and load up to five custom text or HTML templates from the settings page.
* **Auto-Replacing Placeholders**: Automatically swap `[name]`, `[product_title]`, `[product_link]`, and `[order_date]` placeholders with real order details.
* **Order Status Automation**: Sending emails automatically transitions orders to 'Completed' status (or 'Cancelled' when using Template #5).

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it directly via the WordPress Plugins dashboard.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your templates under **WooCommerce -> Mewshtari Email in Order**.

== Frequently Asked Questions ==

=== Does it store the content of sent emails per order? ===
No, this plugin does not store individual email histories in the database.

=== Can I customize the cancellation template? ===
Yes, Template 5 acts as the default cancellation template and automatically transitions order statuses to 'Cancelled' upon sending.

== Changelog ==

=== 1.0.0 ===
* Initial Release.
