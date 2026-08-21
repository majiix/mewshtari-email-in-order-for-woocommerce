# Project Documentation

## Overview
**Mewshtari Email in Order for WooCommerce** allows store administrators to configure customizable HTML email templates mapped to WooCommerce order statuses and dispatch them directly from order editing screens with live placeholder resolution, a 10-second cancel countdown, and order status automaton.

## Tech Stack & Compatibility
- **WordPress**: Requires at least 6.0 | Tested up to 7.1.0
- **WooCommerce**: Requires at least 8.0 | Tested up to 11.0.0
- **PHP**: 8.0+ (supports PHP 8.1, 8.2, 8.3, 8.4)
- **High-Performance Order Storage (HPOS)**: Fully supported (`custom_order_tables` declared compatibility)
- **Frontend**: Vanilla JavaScript (ES6+), scoped modern CSS, TinyMCE / Quicktags API integration

## Dependencies
- WordPress Core (Settings API, Meta Box API, Enqueue Scripts)
- WooCommerce Core (`WC_Order`, `WC_Product`, `WC_Emails` / Mailer subsystem, `FeaturesUtil`, `OrderUtil`)

## Architecture
- `mewshtari-email-in-order-for-woocommerce.php`: Plugin bootstrapper, constant declarations, HPOS feature compatibility registration (`before_woocommerce_init`), and singleton loader (`plugins_loaded`).
- `includes/class-mewshtari-email-in-order.php`: Main coordinator, singleton instance manager, order data extraction, and placeholder compilation.
- `includes/class-mewshtari-email-in-order-admin.php`: Settings panel controller (`woocommerce_page_mewshtari-email-orders`), option sanitization, repeater rendering, and secure AJAX saving handler.
- `includes/class-mewshtari-email-in-order-metabox.php`: Order metabox controller, HPOS and CPT screen registration, template rendering, and AJAX email transmission via `WC()->mailer()`.
- `includes/class-mewshtari-email-in-order-injector.php`: Transactional email hook injector (`woocommerce_email_before_order_table`).
- `assets/`: Enqueued scoped stylesheet and JavaScript assets.

## Features
- **Status-Mapped HTML Templates**: Unlimited templates mapped to WooCommerce order statuses.
- **HPOS Compatible**: Seamless operation on custom orders tables (`woocommerce_page_wc-orders`) and traditional posts (`shop_order`).
- **Live Placeholder Previews**: Automatic client-side and server-side placeholder substitution (`[name]`, `[product_title]`, `[product_link]`, `[products_title]`, `[product_title_with_link]`, `[products_title_with_links]`, `[order_date]`).
- **10-Second Undo Window**: Interactive countdown enabling dispatch cancellation before AJAX execution.
- **Timeline Integration**: Automatic internal order notes logging template name, subject, and recipient address.

## Verification Commands
To validate PHP syntax across all plugin files:
```powershell
rtk php -l mewshtari-email-in-order-for-woocommerce.php
rtk php -l includes/class-mewshtari-email-in-order.php
rtk php -l includes/class-mewshtari-email-in-order-admin.php
rtk php -l includes/class-mewshtari-email-in-order-metabox.php
rtk php -l includes/class-mewshtari-email-in-order-injector.php
```
