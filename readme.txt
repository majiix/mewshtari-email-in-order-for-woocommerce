=== Mewshtari Email in Order for WooCommerce ===
Contributors: micromax2
Tags: woocommerce, email, status, crm, order
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Dynamically send custom status-mapped HTML emails to customers from WooCommerce orders.

== Description ==

Mewshtari Email in Order for WooCommerce allows store owners to define custom HTML templates, map them to order statuses, and dispatch them directly from the WooCommerce Order Edit screen via a secure AJAX interface.

=== Features ===
* **Custom Status-Mapped Templates**: Define unlimited email templates. Map each template to any native WooCommerce order status (Pending, Processing, Completed, Cancelled, etc.).
* **Admin Settings Panel**: Integrated under the WooCommerce settings submenu. Interactive, responsive template repeater allows you to drag/reorder, add, and delete templates instantly.
* **Rich HTML Editors**: Built-in support for the WordPress visual `wp_editor`, enabling formatting of raw/rich HTML structures directly in settings.
* **Order Editing Metabox**: Responsive, elegant metabox displayed on the WooCommerce Order Edit page (supports HPOS and legacy posts).
* **Live Placeholder Previews**: Selecting a template dynamically replaces shortcode placeholders with actual live order data before dispatch.
* **10-Second Countdown & Undo**: Safety countdown delay lets you cancel/undo the send action within 10 seconds.
* **Click-to-Copy Badges**: Quickly copy available dynamic placeholders to your clipboard directly from the metabox.
* **Status Automation**: Automatically transition the order to the mapped status upon successful AJAX dispatch.
* **Internal Order Notes**: Records detailed internal notes on the order timeline when emails are dispatched.
* **Core Hook Integration**: Prepend custom template content to standard WooCommerce transactional emails automatically.

== How It Differs from Default WooCommerce Emails ==

While WooCommerce has standard transactional emails, they are rigid, automated, and triggered only on specific system transitions. This plugin bridges that gap by offering manual control, custom content formatting, and interactive timeline integration:

* **Manual On-Demand Dispatch vs. Automation**: Standard WooCommerce emails trigger automatically on status changes. With this plugin, you can select templates, customize their subjects/bodies, and dispatch them manually at any point from the order edit screen.
* **Interactive Live Previews**: WooCommerce default templates cannot be previewed with real order data before sending. This plugin replaces placeholders in real-time, allowing you to preview the exact content (such as customer billing name, product title and links, order date) in the metabox before sending.
* **Safety Countdown & Undo**: WooCommerce sends emails instantly with no way to cancel. This plugin gives you a 10-second visual delay countdown to cancel/abort the dispatch if you select the wrong template or spot a mistake.
* **Audit Trail Integration**: Default WooCommerce email actions do not leave detailed transaction traces. This plugin automatically logs an internal note to the WooCommerce order timeline, recording the exact template name, subject, and recipient address.
* **Visual Admin Controls**: No PHP coding or template overriding is required. Manage and design templates directly from a visual settings panel with rich WYSIWYG editor support and drag-and-drop ordering.

== Why You Should Use This Plugin ==

* **Tailor Communications**: Send customized messages, download guides, or personalized instructions that depend on the specific products ordered.
* **Avoid Communication Mistakes**: Use the 10-second cancel window to avoid sending incorrect information to customers.
* **Automate Workflows**: Save admin clicks by automatically changing the order status to a mapped status when sending a matching email template.
* **Keep the Team Aligned**: Detailed order notes ensure that every team member knows what communications have been sent to the customer and when.
* **Simple & Elegant Setup**: Enjoy a premium admin dashboard with live guides, interactive panels, and visual controls out-of-the-box.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your templates under 'WooCommerce' -> 'Email in Orders' menu.

== Frequently Asked Questions ==

= How do I use the dynamic placeholders? =
You can copy placeholder tags from the sticky guide in settings or from the copy badges inside the order metabox. Available placeholders:
* `[name fallback="Valued Customer"]` - Customer's full billing name. Supports custom fallback value.
* `[product_title]` - Title of the first physical/non-virtual item found in the order.
* `[product_link]` - URL link to the first item found in the order.
* `[products_title]` - Titles of all items found in the order (comma-separated for subjects, bulleted list for HTML).
* `[product_title_with_link]` - Title of the first item wrapped in an HTML link to the product permalink.
* `[products_title_with_links]` - Titles of all items in the order, wrapped in HTML links.
* `[order_date]` - The site-localized creation date of the order.

= Is High-Performance Order Storage (HPOS) supported? =
Yes, the plugin is fully compatible with both the traditional WordPress post-based orders database and the new WooCommerce High-Performance Order Storage (HPOS) tables.

= How does the 10-second countdown work? =
When you click "Send to customer now" in the metabox, a 10-second countdown begins. The button turns red and provides a cancellation prompt. You can click this to abort the send action at any time before the timer expires.

== Changelog ==

= 1.2.0 =
* Updated documentation to explain differences from default WooCommerce email features and benefits.

= 1.1.0 =
* Unified placeholder parsing logic between backend hooks and metabox previewers.
* Modularized single-class structure into split component controllers in the `includes/` folder.
* Applied WordPress security standards (capabilities, sanitization, nonces, and unslashing).
* Integrated custom delay countdowns and interactive metabox animations.
* Extracted inline CSS and JS into enqueued external stylesheet and script assets.
* Declared `Domain Path` and loaded text domain on init to make the plugin fully localization-ready.

= 1.0.0 =
* Initial release of the plugin.
