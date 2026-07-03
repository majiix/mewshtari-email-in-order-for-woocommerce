# Features: Mewshtari Email in Order for WooCommerce

## Custom Status-Mapped Templates
- Define unlimited email templates.
- Support for custom email subject lines per template, supporting dynamic placeholder replacements.
- Map each template to any native WooCommerce order status (Pending, Processing, Completed, Cancelled, etc.).
- Store raw/rich HTML structures inside templates.

## Admin Settings Panel
- Integrated under WooCommerce settings submenu.
- Interactive, responsive Vanilla JS template repeater (drag/reorder, add, delete instantly).
- Responsive layouts with premium aesthetics (card layouts, custom CSS, shadows, modern badges).
- Informational guide on template shortcodes (`[name]`, `[product_title]`, `[product_link]`, `[order_date]`).

## Order Editing Metabox
- "Mewshtari Email in Order" metabox shown on WooCommerce order edit page.
- Native compatibility with HPOS (High-Performance Order Storage) and post-based orders.
- Template select dropdown dynamically loads raw template HTML, replaces shortcodes with live order data, and updates `wp_editor` body instantly.
- Primary AJAX trigger "Send to customer now" with visual loading feedback.

## Automation & Processing
- Send secure email transmissions via WooCommerce core mailer.
- Automatic status update to mapped status upon successful AJAX dispatch.
- Record internal order notes detailing the transmission.
- Seamless prepending of the custom HTML content directly into WooCommerce transactional emails using standard action hooks.
