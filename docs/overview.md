# Project Overview: Mewshtari Email in Order for WooCommerce

## Tech Stack
- PHP 8.0+
- WordPress Core APIs (Settings API, Meta Boxes, AJAX, wp_editor)
- WooCommerce Core APIs (Orders, Mailer, HPOS)
- HTML5, Vanilla JavaScript, CSS3

## Architecture
- All PHP and client-side logic is housed in the single bootstrap file: [mewshtari-email-in-order-for-woocommerce.php](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/mewshtari-email-in-order-for-woocommerce.php).
- Object-oriented architecture using a primary controller class `Mewshtari_Email_In_Order` initialized on the `plugins_loaded` hook.
- Data storage:
  - Global settings are stored in `mewshtari_email_templates` option as a serialized array of templates.
  - Per-order custom email contents are saved in order metadata (`_mewshtari_email_subject`, `_mewshtari_email_body`).

## Verification Commands
- PHP Syntax Linting:
  ```powershell
  php -l e:\wps\dorsanet\app\public\wp-content\plugins\mewshtari-email-in-order-for-woocommerce\mewshtari-email-in-order-for-woocommerce.php
  ```
