# Project Overview: Mewshtari Email in Order for WooCommerce

## Tech Stack
- PHP 8.0+
- WordPress Core APIs (Settings API, Meta Boxes, AJAX, wp_editor)
- WooCommerce Core APIs (Orders, Mailer, HPOS)
- HTML5, Vanilla JavaScript, CSS3

## Architecture
- The PHP logic is modularized into dedicated controller classes located in the `includes/` directory:
  - [mewshtari-email-in-order-for-woocommerce.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/mewshtari-email-in-order-for-woocommerce.php): Main plugin bootstrap file.
  - [class-mewshtari-email-in-order.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/includes/class-mewshtari-email-in-order.php): Main controller singleton and unified placeholder helper.
  - [class-mewshtari-email-in-order-admin.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/includes/class-mewshtari-email-in-order-admin.php): Handles settings page menu, repeater rendering, and settings saving.
  - [class-mewshtari-email-in-order-metabox.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/includes/class-mewshtari-email-in-order-metabox.php): Handles metabox registration, layouts, and AJAX email dispatch.
  - [class-mewshtari-email-in-order-injector.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/includes/class-mewshtari-email-in-order-injector.php): Handles injection of content into transactional emails.
- Data storage:
  - Global settings are stored in `mewshtari_email_templates` option as a serialized array of templates.

## Verification Commands
- PHP Syntax Linting:
  ```powershell
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\mewshtari-email-in-order-for-woocommerce.php
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\includes\class-mewshtari-email-in-order.php
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\includes\class-mewshtari-email-in-order-admin.php
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\includes\class-mewshtari-email-in-order-metabox.php
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\includes\class-mewshtari-email-in-order-injector.php
  ```
